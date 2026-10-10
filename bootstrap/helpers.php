<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Response;

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        /** @var Application $app */
        $app = $GLOBALS['app'];

        if ($abstract === null) {
            return $app;
        }

        return $app->container()->make($abstract);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        $config = app('config');
        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('response')) {
    function response(string $body = '', int $status = 200, array $headers = []): Response
    {
        return new Response($body, $status, $headers);
    }
}
