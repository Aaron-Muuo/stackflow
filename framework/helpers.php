<?php

declare(strict_types=1);

/**
 * StackFlow Global Helper Functions
 *
 * These functions are available everywhere in the application.
 * They provide convenient shortcuts to framework features.
 */

use StackFlow\Core\Application;
use StackFlow\Core\Config;
use StackFlow\Core\Env;
use StackFlow\Core\Session;
use StackFlow\Core\Response;

if (!function_exists('env')) {
    /**
     * Get an environment variable value.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     * e.g. config('database.default')
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the absolute path to the project root.
     */
    function base_path(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $base;
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the absolute path to the app directory.
     */
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('framework_path')) {
    /**
     * Get the absolute path to the framework directory.
     */
    function framework_path(string $path = ''): string
    {
        return base_path('framework' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the absolute path to the storage directory.
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the absolute path to the resources directory.
     */
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the absolute path to the public directory.
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('session')) {
    /**
     * Get or set session data.
     * session('key') → get
     * session('key', 'value') → set
     * session() → returns Session class
     */
    function session(string $key = null, mixed $value = null): mixed
    {
        if ($key === null) {
            return Session::class;
        }
        if ($value !== null) {
            Session::set($key, $value);
            return null;
        }
        return Session::get($key);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response.
     */
    function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back to the previous page.
     */
    function back(): Response
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        return Response::redirect($url);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters (like htmlspecialchars).
     */
    function e(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the application.
     */
    function url(string $path = ''): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL to a public asset.
     */
    function asset(string $path): string
    {
        return url('/' . ltrim($path, '/'));
    }
}

if (!function_exists('now')) {
    /**
     * Get the current datetime string.
     */
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump variables and die (developer utility).
     */
    function dd(mixed ...$values): never
    {
        \StackFlow\Debug\Debugger::dd(...$values);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables without dying.
     */
    function dump(mixed ...$values): void
    {
        \StackFlow\Debug\Debugger::dump(...$values);
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HTTP exception.
     */
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        if ($code === 404) {
            $path = $_SERVER['REQUEST_URI'] ?? '/';
            header('Content-Type: text/html; charset=UTF-8');
            echo \StackFlow\Debug\ErrorHandler::renderNotFoundPage($path, $message ?: 'Page Not Found');
            exit;
        }

        $messages = [403 => 'Forbidden', 500 => 'Internal Server Error'];
        $text = $message ?: ($messages[$code] ?? 'Error');
        echo "<h1>$code — $text</h1>";
        exit;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token for the current session.
     */
    function csrf_token(): string
    {
        return Session::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a hidden CSRF input field.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('span')) {
    /**
     * Create a Span widget instance with text as the first argument.
     *
     * Example: span("Don't have an account?", ['class' => 'text-gray-500'])
     */
    function span(string|array $textOrProps = '', array $props = []): \StackFlow\Widgets\Display\Span
    {
        return \StackFlow\Widgets\Display\Span::make($textOrProps, $props);
    }
}
