<?php

declare(strict_types=1);

namespace StackFlow\Core;

use Closure;
use Throwable;

/**
 * StackFlow Application
 *
 * The central bootstrap class for the StackFlow framework. Implements a singleton
 * pattern and acts as the service container (IoC). Responsible for loading
 * environment variables, configuration, registering core bindings, and
 * dispatching the HTTP request through the router.
 *
 * @package StackFlow\Core
 */
final class Application
{
    /**
     * Singleton instance of the Application.
     */
    private static ?Application $instance = null;

    /**
     * The absolute base path of the application (project root).
     */
    public readonly string $basePath;

    /**
     * Registered DI bindings: abstract => factory Closure.
     *
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * Resolved singleton instances from the container.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Private constructor — use boot() to create or retrieve the instance.
     *
     * @param string $basePath Absolute path to the project root.
     */
    private function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    /**
     * Boot the application.
     *
     * Performs the full bootstrap sequence:
     *  1. Creates (or returns existing) singleton instance.
     *  2. Loads environment variables from `.env`.
     *  3. Loads PHP configuration files from `app/config/`.
     *  4. Installs a global error/exception handler.
     *  5. Registers default service bindings.
     *
     * @param  string $basePath Absolute path to the project root.
     * @return static           The singleton Application instance.
     */
    public static function boot(string $basePath): static
    {
        if (static::$instance === null) {
            static::$instance = new static($basePath);
            static::$instance->initialize();
        }

        return static::$instance;
    }

    /**
     * Run the application.
     *
     * Captures the current HTTP request, passes it through the router,
     * and sends the resulting response to the client.
     *
     * @return void
     */
    public function run(): void
    {
        $request  = $this->make(Request::class);
        $router   = $this->make(Router::class);
        $response = $router->dispatch($request);
        $response->send();
    }

    // -------------------------------------------------------------------------
    // Singleton access
    // -------------------------------------------------------------------------

    /**
     * Retrieve the current singleton instance.
     *
     * @return static|null The Application instance, or null if not yet booted.
     */
    public static function getInstance(): ?static
    {
        return static::$instance;
    }

    // -------------------------------------------------------------------------
    // Service Container (IoC)
    // -------------------------------------------------------------------------

    /**
     * Register a binding in the container.
     *
     * The factory Closure receives the Application instance so it can resolve
     * nested dependencies.
     *
     * @param  string  $abstract The service identifier (usually a FQCN).
     * @param  Closure $factory  A factory that produces the service.
     * @return void
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        // Clear any cached instance so the new factory takes precedence.
        unset($this->instances[$abstract]);
    }

    /**
     * Register a shared (singleton) binding in the container.
     *
     * The factory is called only once; subsequent calls return the same instance.
     *
     * @param  string  $abstract The service identifier.
     * @param  Closure $factory  A factory that produces the shared service.
     * @return void
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bind($abstract, function (Application $app) use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($app);
            }
            return $this->instances[$abstract];
        });
    }

    /**
     * Resolve a service from the container.
     *
     * If a binding exists the factory is invoked; otherwise the framework
     * attempts to instantiate the class directly (no-arg constructor).
     *
     * @param  string $abstract The service identifier.
     * @return mixed            The resolved service.
     *
     * @throws \RuntimeException If the service cannot be resolved.
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException(
            "StackFlow: Unable to resolve [{$abstract}] from the container."
        );
    }

    // -------------------------------------------------------------------------
    // Path helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a path relative to the application base path.
     *
     * @param  string $path Optional sub-path to append.
     * @return string       Absolute file-system path.
     */
    public function path(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    /**
     * Resolve a path relative to the `app/` directory.
     *
     * @param  string $path Optional sub-path to append.
     * @return string       Absolute file-system path.
     */
    public function appPath(string $path = ''): string
    {
        return $this->path('app' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }

    /**
     * Resolve a path relative to the `framework/` directory.
     *
     * @param  string $path Optional sub-path to append.
     * @return string       Absolute file-system path.
     */
    public function frameworkPath(string $path = ''): string
    {
        return $this->path('framework' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }

    // -------------------------------------------------------------------------
    // Internal initialization
    // -------------------------------------------------------------------------

    /**
     * Run internal bootstrap steps after the singleton is created.
     *
     * @return void
     */
    private function initialize(): void
    {
        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->registerErrorHandler();
        \StackFlow\Theme\ThemeManager::boot();
        $dbConfig = Config::get('database');
        if (is_array($dbConfig)) {
            \StackFlow\Database\DB::configure($dbConfig);
        }
        $this->registerDefaultBindings();
    }

    /**
     * Load the `.env` file from the project root.
     *
     * @return void
     */
    private function loadEnvironment(): void
    {
        Env::load($this->basePath);
    }

    /**
     * Load all PHP configuration files from `app/config/`.
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        Config::load($this->basePath);
    }

    /**
     * Register a global exception/error handler that produces clean output.
     *
     * In production the user sees a generic message; in development the full
     * exception trace is printed. Controlled by APP_DEBUG env variable.
     *
     * @return void
     */
    private function registerErrorHandler(): void
    {
        \StackFlow\Debug\Debugger::boot();
        $debug = filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
        \StackFlow\Debug\ErrorHandler::register($debug);
    }

    /**
     * Register default framework service bindings.
     *
     * @return void
     */
    private function registerDefaultBindings(): void
    {
        // Request - capture once and reuse
        $this->singleton(Request::class, fn() => Request::capture());

        // Router - stateless, just needs to be instantiated
        $this->singleton(Router::class, fn() => new Router());

        // Session - automatically started
        $this->singleton(Session::class, static function (): Session {
            Session::start();
            return new Session();
        });

        // Auth - depends on Session
        $this->singleton(Auth::class, fn() => new Auth());

        // Config - already loaded statically, expose as object if needed
        $this->bind(Config::class, fn() => new Config());
    }
}
