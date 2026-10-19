<?php

declare(strict_types=1);

/**
 * StackFlow PHP Framework - Development Server Router
 *
 * Emulates web server rewrite functionality for PHP's built-in CLI web server.
 * Allows running `php stackflow serve` directly from the project root.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'
);

$publicPath = __DIR__ . '/public';

// If the requested URI exists as a real file in public/, let PHP serve it directly
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

// Otherwise, delegate to the public front-controller
require_once $publicPath . '/index.php';
