<?php

declare(strict_types=1);

/**
 * StackFlow PHP Framework - Public Web Entry Point
 */

define('BASE_PATH', dirname(__DIR__));
define('STACKFLOW_START', microtime(true));

// Autoload dependencies and framework classes
require BASE_PATH . '/vendor/autoload.php';

// Boot the StackFlow application
$app = \StackFlow\Core\Application::boot(BASE_PATH);

// Load routes
if (file_exists(BASE_PATH . '/routes/web.php')) {
    require BASE_PATH . '/routes/web.php';
}
if (file_exists(BASE_PATH . '/routes/api.php')) {
    require BASE_PATH . '/routes/api.php';
}

// Run the application and send HTTP response
$app->run();