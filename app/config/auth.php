<?php

declare(strict_types=1);

return [
    'default' => 'web',
    'guards' => [
        'web' => [
            'driver' => 'session',
            'model'  => 'App\\Models\\User',
        ],
        'api' => [
            'driver' => 'token',
            'model'  => 'App\\Models\\User',
        ],
    ],
    'passwords' => [
        'min_length'        => 8,
        'require_uppercase' => true,
    ],
];
