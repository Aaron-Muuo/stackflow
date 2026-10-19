<?php

declare(strict_types=1);

namespace StackFlow\Debug;

use Throwable;

/**
 * Global error and exception handler for StackFlow.
 *
 * Call `ErrorHandler::register()` once during application bootstrap to
 * replace PHP's default error output with StackFlow's pretty error pages.
 *
 * In **debug mode** the error page shows:
 *  - Exception class + message
 *  - File + line number
 *  - Highlighted code snippet (± `$context` lines)
 *  - Full collapsible stack trace
 *  - Request info (method, URL, GET/POST data — sanitized)
 *  - Environment info (PHP version, memory, peak memory)
 *
 * In **production mode** a generic "something went wrong" page is shown with
 * no internal details exposed to the end user.
 */
class ErrorHandler
{
    /** @var bool Whether debug mode is active. */
    private static bool $debug = false;

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register PHP error and exception handlers.
     *
     * @param bool $debug `true` for full debug output, `false` for generic page.
     */
    public static function register(bool $debug = true): void
    {
        self::$debug = $debug;

        set_exception_handler(static function (Throwable $e): void {
            self::handleException($e);
        });

        set_error_handler(static function (
            int    $errno,
            string $errstr,
            string $errfile,
            int    $errline,
        ): bool {
            return self::handleError($errno, $errstr, $errfile, $errline);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handleError(
                    $error['type'],
                    $error['message'],
                    $error['file'],
                    $error['line'],
                );
            }
        });
    }

    // -------------------------------------------------------------------------
    // Exception handler
    // -------------------------------------------------------------------------

    /**
     * Render a pretty error page for an uncaught exception.
     *
     * @param Throwable $e The uncaught exception or error.
     */
    private static function handleException(Throwable $e): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }

        if (! headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html; charset=UTF-8');
        }

        if (self::$debug) {
            echo self::renderDebugPage($e);
        } else {
            echo self::renderProductionPage();
        }
    }

    // -------------------------------------------------------------------------
    // Error handler
    // -------------------------------------------------------------------------

    /**
     * Convert PHP errors into exceptions so they reach the exception handler.
     *
     * Returns `false` for suppressed errors (@-operator), delegating to PHP's
     * internal handler. Returns `true` for all other errors so PHP does not
     * additionally write to the error log.
     *
     * @param int    $errno   The error level constant (E_WARNING, etc.).
     * @param string $errstr  Human-readable error message.
     * @param string $errfile Absolute path to the file where the error occurred.
     * @param int    $errline Line number in $errfile.
     * @return bool
     */
    private static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Respect the @ error-suppression operator.
        if (!(error_reporting() & $errno)) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    // -------------------------------------------------------------------------
    // Page rendering
    // -------------------------------------------------------------------------

    /**
     * Render the full debug error page.
     */
    private static function renderDebugPage(Throwable $e): string
    {
        $file        = $e->getFile();
        $line        = $e->getLine();
        $codeSnippet = self::getCodeSnippet($file, $line);
        $stackTrace  = $e->getTrace();

        // Gather request info (sanitize sensitive POST fields).
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'url'    => self::currentUrl(),
            'get'    => self::sanitize($_GET),
            'post'   => self::sanitize($_POST),
            'headers'=> self::safeHeaders(),
        ];

        $viewFile = __DIR__ . '/views/error.php';

        if (file_exists($viewFile)) {
            // Render via the PHP template.
            ob_start();
            $exception = $e;
            include $viewFile;
            return (string) ob_get_clean();
        }

        // Fallback: inline minimal error page.
        return self::renderMinimalPage($e, $codeSnippet, $stackTrace, $request);
    }

    /**
     * Render a generic production error page (no internal details).
     */
    private static function renderProductionPage(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Server Error — Stackflow</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    background: #1a1a2e;
                    color: #e2e8f0;
                    font-family: system-ui, -apple-system, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    padding: 2rem;
                }
                .card {
                    text-align: center;
                    max-width: 480px;
                }
                .code { font-size: 6rem; font-weight: 800; color: #ef4444; line-height: 1; }
                h1   { font-size: 1.5rem; font-weight: 600; margin-top: 1rem; }
                p    { margin-top: 0.75rem; color: #94a3b8; line-height: 1.6; }
                a    { color: #818cf8; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="code">500</div>
                <h1>Something went wrong.</h1>
                <p>
                    An unexpected error occurred. Please try again later.<br>
                    If the problem persists, <a href="mailto:support@example.com">contact support</a>.
                </p>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Render the 404 Not Found page matching the error reporting theme.
     *
     * @param string $path The requested path that could not be found.
     * @param string $message Optional custom not found message.
     * @return string Complete HTML document string.
     */
    public static function renderNotFoundPage(string $path = '/', string $message = 'Page Not Found'): string
    {
        $viewFile = __DIR__ . '/views/404.php';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $debug = self::$debug || filter_var(\StackFlow\Core\Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
        $env = (string) \StackFlow\Core\Env::get('APP_ENV', 'production');
        $routes = class_exists(\StackFlow\Core\Router::class) ? \StackFlow\Core\Router::getRoutes() : [];

        if (file_exists($viewFile)) {
            ob_start();
            include $viewFile;
            return (string) ob_get_clean();
        }

        // Minimal fallback if template was deleted
        $escapedPath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>404 | Not Found — Stackflow</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-4">
            <div class="flex items-center gap-6">
                <span class="text-6xl font-bold font-mono text-white">404</span>
                <span class="h-12 w-px bg-gray-800"></span>
                <span class="text-gray-400 font-mono tracking-widest uppercase">NOT FOUND</span>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Inline fallback debug page when the error template is missing.
     *
     * @param Throwable                     $e
     * @param array<int, array<string, mixed>> $codeSnippet
     * @param array<int, array<string, mixed>> $stackTrace
     * @param array<string, mixed>          $request
     */
    private static function renderMinimalPage(
        Throwable $e,
        array     $codeSnippet,
        array     $stackTrace,
        array     $request,
    ): string {
        $class   = htmlspecialchars(get_class($e),   ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file    = htmlspecialchars($e->getFile(),    ENT_QUOTES, 'UTF-8');
        $line    = $e->getLine();

        $traceHtml = '';
        foreach ($stackTrace as $i => $frame) {
            $frameFile = htmlspecialchars($frame['file'] ?? '[internal]', ENT_QUOTES, 'UTF-8');
            $frameLine = $frame['line'] ?? 0;
            $func      = htmlspecialchars(
                ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                ENT_QUOTES, 'UTF-8'
            );
            $traceHtml .= "<div class=\"frame\">#{$i} {$func}() &mdash; {$frameFile}:{$frameLine}</div>";
        }

        $snippetHtml = '';
        foreach ($codeSnippet as $row) {
            $isError  = $row['line'] === $line;
            $lineNum  = htmlspecialchars((string) $row['line'], ENT_QUOTES, 'UTF-8');
            $code     = htmlspecialchars($row['code'],           ENT_QUOTES, 'UTF-8');
            $cls      = $isError ? ' error-line' : '';
            $snippetHtml .= "<div class=\"code-line{$cls}\"><span class=\"ln\">{$lineNum}</span>{$code}</div>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Error — {$class}</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{background:#1a1a2e;color:#e2e8f0;font-family:system-ui,sans-serif;padding:2rem}
                .exception-class{color:#f87171;font-size:1.75rem;font-weight:700;word-break:break-all}
                .message{color:#fca5a5;font-size:1.1rem;margin:.75rem 0 1.5rem;line-height:1.5}
                .location{color:#94a3b8;font-size:.875rem;margin-bottom:1.5rem}
                h3{color:#a5b4fc;margin-bottom:.5rem;font-size:.875rem;text-transform:uppercase;letter-spacing:.1em}
                .snippet,.trace{background:#16213e;border-radius:.5rem;padding:1rem;margin-bottom:1.5rem;overflow-x:auto}
                .code-line{font-family:monospace;font-size:.8rem;padding:.15rem .5rem;white-space:pre}
                .error-line{background:#7f1d1d;border-left:3px solid #ef4444;color:#fecaca}
                .ln{display:inline-block;width:3rem;color:#4b5563;user-select:none}
                .frame{font-size:.8rem;padding:.35rem 0;border-bottom:1px solid #1e293b;color:#94a3b8}
                .frame:last-child{border:none}
            </style>
        </head>
        <body>
            <div class="exception-class">{$class}</div>
            <div class="message">{$message}</div>
            <div class="location">{$file} : line {$line}</div>

            <h3>Code Snippet</h3>
            <div class="snippet">{$snippetHtml}</div>

            <h3>Stack Trace</h3>
            <div class="trace">{$traceHtml}</div>
        </body>
        </html>
        HTML;
    }

    // -------------------------------------------------------------------------
    // Code snippet
    // -------------------------------------------------------------------------

    /**
     * Extract lines around an error from a source file.
     *
     * @param string $file    Absolute path to the source file.
     * @param int    $line    The 1-based error line number.
     * @param int    $context Number of surrounding lines to include on each side.
     * @return array<int, array{line: int, code: string}>
     */
    private static function getCodeSnippet(string $file, int $line, int $context = 7): array
    {
        if (! is_readable($file)) {
            return [];
        }

        $lines  = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $total  = count($lines);
        $start  = max(0, $line - $context - 1);
        $end    = min($total - 1, $line + $context - 1);

        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $snippet[] = [
                'line' => $i + 1,
                'code' => $lines[$i],
            ];
        }

        return $snippet;
    }

    // -------------------------------------------------------------------------
    // Request helpers
    // -------------------------------------------------------------------------

    /**
     * Build the current request URL from server globals.
     */
    private static function currentUrl(): string
    {
        if (PHP_SAPI === 'cli') {
            return 'CLI';
        }

        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST']   ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * Redact potentially sensitive field values (passwords, tokens, secrets).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function sanitize(array $data): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'api_key', '_token'];

        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = in_array(strtolower((string) $key), $sensitive, true)
                ? '[REDACTED]'
                : $value;
        }

        return $result;
    }

    /**
     * Collect HTTP request headers, filtering out cookies/auth tokens.
     *
     * @return array<string, string>
     */
    private static function safeHeaders(): array
    {
        if (! function_exists('getallheaders')) {
            return [];
        }

        $headers  = getallheaders();
        $filtered = [];

        foreach ($headers as $name => $value) {
            $lower = strtolower($name);
            if (in_array($lower, ['cookie', 'authorization', 'x-api-key'], true)) {
                $filtered[$name] = '[REDACTED]';
            } else {
                $filtered[$name] = $value;
            }
        }

        return $filtered;
    }
}
