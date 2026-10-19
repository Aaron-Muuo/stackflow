<?php

declare(strict_types=1);

return [
    'default' => env('AI_DRIVER', 'gemini'),
    'drivers' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY', ''),
            'model'   => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY', ''),
            'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'ollama' => [
            'base_url' => env('OLLAMA_URL', 'http://localhost:11434'),
            'model'    => env('OLLAMA_MODEL', 'llama3.2'),
        ],
    ],
];
