<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Jusbee App'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => env('APP_URL', 'http://localhost'),
    'view_path' => dirname(__DIR__) . '/views',
    'timezone' => 'UTC',
    'middleware' => [],
    'security_headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-XSS-Protection' => '1; mode=block',
    ],
];
