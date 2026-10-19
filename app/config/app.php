<?php

declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'Stackflow App'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'UTC',
    'theme'    => env('APP_THEME', 'default'),
    'locale'   => 'en',
];
