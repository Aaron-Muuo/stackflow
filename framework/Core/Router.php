<?php

declare(strict_types=1);

namespace StackFlow\Core;

use Closure;

/**
 * StackFlow Router
 *
 * A fast, static-registration URL router supporting GET, POST, and grouped
 * middleware routes. Routes are matched in registration order. Named parameters
 * (e.g. `/users/{id}`) are extracted and injected into the Request object.
 *
 * Usage:
 * ```php
 * Router::get('/login',  LoginPage::class);
 * Router::post('/login', LoginPage::class);
 * Router::group(['auth'], function() {
 *     Router::get('/dashboard', DashboardPage::class);
 * });
 * ```
 *
 * @package StackFlow\Core
 */
class Router
{
    /**
     * All registered routes.
     *
     * Each entry shape:
     * ```
     * [
     *   'method'     => 'GET',
     *   'path'       => '/users/{id}',
     *   'page'       => UserPage::class,
     *   'middleware' => ['auth'],
     * ]
     * ```
     *
     * @var array<int, array{method: string, path: string, page: string, middleware: array<string>}>
     */
    private static array $routes = [];

    /**
     * Middleware stack applied to the currently executing group().
     *
     * @var array<string>
     */
    private static array $groupMiddleware = [];

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    /**
     * Register a GET route.
     *
     * @param  string|Closure|array $handler URL path, Page class, Controller [Class, 'method'], or Closure.
     * @param  string|Closure|array $pageClass  Target handler.
     * @param  array<string>        $middleware Middleware identifiers to run before the route.
     * @return void
     */
    public static function get(string $path, string|Closure|array $pageClass, array $middleware = []): void
    {
        static::addRoute('GET', $path, $pageClass, $middleware);
    }

    /**
     * Register a POST route.
     *
     * @param  string               $path       URL path.
     * @param  string|Closure|array $pageClass  Target handler.
     * @param  array<string>        $middleware Middleware identifiers to run before the route.
     * @return void
     */
    public static function post(string $path, string|Closure|array $pageClass, array $middleware = []): void
    {
        static::addRoute('POST', $path, $pageClass, $middleware);
    }

    /**
     * Register a PUT route.
     */
    public static function put(string $path, string|Closure|array $pageClass, array $middleware = []): void
    {
        static::addRoute('PUT', $path, $pageClass, $middleware);
    }

    /**
     * Register a PATCH route.
     */
    public static function patch(string $path, string|Closure|array $pageClass, array $middleware = []): void
    {
        static::addRoute('PATCH', $path, $pageClass, $middleware);
    }

    /**
     * Register a DELETE route.
     */
    public static function delete(string $path, string|Closure|array $pageClass, array $middleware = []): void
    {
        static::addRoute('DELETE', $path, $pageClass, $middleware);
    }

    /**
     * Group routes under shared middleware.
     *
     * Middleware defined at the group level is merged with per-route middleware.
     *
     * @param  array<string> $middleware Middleware identifiers applied to every route inside.
     * @param  Closure       $callback   Closure that registers routes.
     * @return void
     */
    public static function group(array $middleware, Closure $callback): void
    {
        $previous             = static::$groupMiddleware;
        static::$groupMiddleware = array_merge($previous, $middleware);

        $callback();

        static::$groupMiddleware = $previous;
    }

    /**
     * Return all registered routes (useful for debugging / testing).
     *
     * @return array<int, array{method: string, path: string, page: string, middleware: array<string>}>
     */
    public static function getRoutes(): array
    {
        return static::$routes;
    }

    /**
     * Clear all registered routes (useful in tests).
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$routes         = [];
        static::$groupMiddleware = [];
    }

    // -------------------------------------------------------------------------
    // Dispatching
    // -------------------------------------------------------------------------

    /**
     * Dispatch the incoming request.
     *
     * 1. Strips the query string from the URI.
     * 2. Iterates routes looking for a path match.
     * 3. On a path match, checks the HTTP method (405 if wrong).
     * 4. Runs middleware pipeline.
     * 5. Instantiates the Page class, calls `build()`, wraps in Response.
     *
     * @param  Request  $request The incoming HTTP request.
     * @return Response          The response to send to the client.
     */
    public function dispatch(Request $request): Response
    {
        $method = strtoupper($request->method);
        $path   = '/' . trim($request->path, '/');

        $pathMatched = false;

        foreach (static::$routes as $route) {
            $params = [];

            if (!$this->matchPath($route['path'], $path, $params)) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            // Inject route params into the request
            $request->params = $params;

            // Run middleware pipeline — each middleware may return a Response to short-circuit
            $middlewareResponse = $this->runMiddleware($route['middleware'], $request);
            if ($middlewareResponse !== null) {
                return $middlewareResponse;
            }

            // Resolve and invoke the page
            return $this->invokePage($route['page'], $request);
        }

        return $pathMatched
            ? $this->methodNotAllowed($method, $path, $request)
            : $this->notFound($path, $request);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Push a single route into the registry.
     *
     * @param  string        $method
     * @param  string        $path
     * @param  string        $pageClass
     * @param  array<string> $middleware
     * @return void
     */
    private static function addRoute(string $method, string $path, string|Closure|array $pageClass, array $middleware): void
    {
        static::$routes[] = [
            'method'     => strtoupper($method),
            'path'       => '/' . trim($path, '/'),
            'page'       => $pageClass,
            'middleware' => array_merge(static::$groupMiddleware, $middleware),
        ];
    }

    /**
     * Match a route pattern against the actual request path.
     *
     * Converts `{param}` placeholders to named regex capture groups.
     *
     * @param  string               $pattern Route pattern, e.g. `/users/{id}/posts/{slug}`.
     * @param  string               $path    Actual request path.
     * @param  array<string, string> &$params Populated with captured parameter values.
     * @return bool                          True when the path matches.
     */
    private function matchPath(string $pattern, string $path, array &$params): bool
    {
        // Convert {param} → named capture group (?P<param>[^/]+)
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '@^' . $regex . '$@';

        if (preg_match($regex, $path, $matches) !== 1) {
            return false;
        }

        // Extract only string-keyed (named) matches
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return true;
    }

    /**
     * Run the middleware stack for a route.
     *
     * Each middleware class must implement `handle(Request $request): ?Response`.
     * Returning a non-null Response short-circuits the pipeline.
     *
     * @param  array<string> $middleware List of middleware FQCNs or short names.
     * @param  Request       $request    The current request.
     * @return Response|null             A Response to abort, or null to continue.
     */
    private function runMiddleware(array $middleware, Request $request): ?Response
    {
        foreach ($middleware as $mw) {
            $instance = new $mw();

            if (!method_exists($instance, 'handle')) {
                throw new \RuntimeException(
                    "StackFlow: Middleware [{$mw}] must implement handle(Request \$request): ?Response"
                );
            }

            $result = $instance->handle($request);

            if ($result instanceof Response) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Instantiate and invoke a route handler (Page class, Controller action, or Closure).
     *
     * @param  string|Closure|array $handler
     * @param  Request              $request The current request.
     * @return Response
     */
    private function invokePage(string|Closure|array $handler, Request $request): Response
    {
        // 1. Closures: fn(Request $request): Response|Widget|string|array
        if ($handler instanceof Closure) {
            $result = $handler($request);
            return $this->normalizeResponse($result);
        }

        // 2. Controller array: [Controller::class, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (!class_exists($class)) {
                throw new \RuntimeException("StackFlow: Controller class [{$class}] not found.");
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("StackFlow: Controller method [{$class}::{$method}()] not found.");
            }
            $result = $controller->$method($request);
            return $this->normalizeResponse($result);
        }

        // 3. String class name
        if (is_string($handler)) {
            if (!class_exists($handler)) {
                throw new \RuntimeException("StackFlow: Handler class [{$handler}] not found.");
            }

            $page = new $handler();

            if ($page instanceof \StackFlow\Page\Page) {
                // 0. Run Page-level middleware defined directly on the Page class
                $pageMiddleware = $page->middleware();
                if (!empty($pageMiddleware)) {
                    $mwResponse = $this->runMiddleware($pageMiddleware, $request);
                    if ($mwResponse !== null) {
                        return $mwResponse;
                    }
                }

                // 0.1 Check page authorization
                if (!$page->authorize($request)) {
                    return Response::html('<h1>403 Forbidden</h1>', 403);
                }

                $isAjax = $request->isAjax() || $request->wantsJson();

                // 1. Check for explicit action method: e.g. _action=delete or _action=register
                $actionMethod = (string) ($request->get('_action') ?? '');
                if ($actionMethod !== '' && method_exists($page, $actionMethod)) {
                    $result = $page->$actionMethod($request);
                    if ($result instanceof Response) {
                        return $this->formatAjaxResponseIfNeeded($result, $isAjax);
                    }
                    if (is_array($result) || is_object($result)) {
                        return Response::json($result);
                    }
                }

                // 2. Check if page defines an action hook (for button clicks/mutations/redirects)
                if (method_exists($page, 'action')) {
                    $actionResponse = $page->action($request);
                    if ($actionResponse instanceof Response) {
                        return $this->formatAjaxResponseIfNeeded($actionResponse, $isAjax);
                    }
                    if (is_array($actionResponse) || is_object($actionResponse)) {
                        return Response::json($actionResponse);
                    }
                }

                // 3. For AJAX GET requests: if page implements api() or data(), call it
                if ($isAjax && method_exists($page, 'api')) {
                    $apiResult = $page->api($request);
                    return $this->normalizeResponse($apiResult);
                }

                // 4. Render the page
                $renderer = new \StackFlow\Page\PageRenderer();
                $rendered = $renderer->render($page, $request);

                if ($rendered instanceof Response) {
                    return $this->formatAjaxResponseIfNeeded($rendered, $isAjax);
                }

                // 5. If an AJAX request expects JSON for a rendered page, return JSON payload
                if ($isAjax && $request->wantsJson()) {
                    return Response::json([
                        'status' => 'ok',
                        'title'  => $page->title(),
                        'html'   => $rendered,
                    ]);
                }

                return Response::html($rendered);
            }

            if (method_exists($page, 'build')) {
                $result = $page->build($request);
                return $this->normalizeResponse($result);
            }

            if (is_callable($page)) {
                $result = $page($request);
                return $this->normalizeResponse($result);
            }
        }

        throw new \RuntimeException("StackFlow: Invalid route handler provided.");
    }

    /**
     * Intercept redirects for AJAX requests and return JSON instructions instead.
     */
    private function formatAjaxResponseIfNeeded(Response $response, bool $isAjax): Response
    {
        if (!$isAjax) {
            return $response;
        }

        $headers = $response->getHeaders();
        $location = $headers['Location'] ?? $headers['location'] ?? null;

        if ($location !== null) {
            // Extract flash messages if any were set
            $toast = null;
            $flashSuccess = Session::getFlash('success');
            $flashError = Session::getFlash('error') ?? Session::getFlash('danger');
            $flashInfo = Session::getFlash('info');
            $flashWarning = Session::getFlash('warning');

            if ($flashSuccess) {
                $toast = ['message' => $flashSuccess, 'type' => 'success'];
            } elseif ($flashError) {
                $toast = ['message' => $flashError, 'type' => 'danger'];
            } elseif ($flashInfo) {
                $toast = ['message' => $flashInfo, 'type' => 'info'];
            } elseif ($flashWarning) {
                $toast = ['message' => $flashWarning, 'type' => 'warning'];
            }

            return Response::json([
                'status'   => 'redirect',
                'redirect' => $location,
                'code'     => $response->getStatusCode(),
                'toast'    => $toast,
            ]);
        }

        return $response;
    }

    /**
     * Normalize various handler return types into a valid Response.
     */
    private function normalizeResponse(mixed $result): Response
    {
        return match (true) {
            $result instanceof Response => $result,
            $result instanceof \StackFlow\Widgets\Widget => Response::html($result->render()),
            is_string($result) => Response::html($result),
            is_array($result) || is_object($result) => Response::json($result),
            default => Response::html(''),
        };
    }

    /**
     * Build a 404 Not Found response.
     *
     * @param  string  $path    The unmatched path.
     * @param  Request $request The incoming HTTP request.
     * @return Response
     */
    private function notFound(string $path, Request $request): Response
    {
        if ($request->isAjax() || str_starts_with($path, '/api')) {
            return Response::json([
                'status'  => 'error',
                'code'    => 404,
                'message' => "Resource not found for {$request->method} {$path}",
            ], 404);
        }

        return Response::notFound(message: 'Page Not Found', path: $path);
    }

    /**
     * Build a 405 Method Not Allowed response.
     *
     * @param  string  $method  The disallowed HTTP method.
     * @param  string  $path    The matched path.
     * @param  Request $request The incoming HTTP request.
     * @return Response
     */
    private function methodNotAllowed(string $method, string $path, Request $request): Response
    {
        if ($request->isAjax() || str_starts_with($path, '/api')) {
            return Response::json([
                'status'  => 'error',
                'code'    => 405,
                'message' => "Method {$method} is not allowed for {$path}",
            ], 405);
        }

        return Response::html(
            content: \StackFlow\Debug\ErrorHandler::renderNotFoundPage(
                path: $path,
                message: "Method {$method} is not allowed for {$path}"
            ),
            status: 405,
        );
    }
}
