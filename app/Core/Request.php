<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $cookies,
        private readonly array $server
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = $path !== false ? $path : '/';
        $path = self::stripBasePath($path, $_SERVER);

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $_SERVER
        );
    }

    /**
     * Removes the front controller's base directory (e.g. "/Jusbee_admin/public")
     * from the request path, so routing works whether the app runs at the web
     * server's root or inside a subdirectory.
     *
     * @param array<string,mixed> $server
     */
    private static function stripBasePath(string $path, array $server): string
    {
        $scriptName = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        $basePaths = $basePath === '' ? [] : [$basePath];
        if (strtolower(basename($basePath)) === 'public') {
            $projectPath = rtrim(dirname($basePath), '/');
            if ($projectPath !== '' && $projectPath !== '.') {
                $basePaths[] = $projectPath;
            }
        }

        usort($basePaths, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));
        foreach ($basePaths as $candidate) {
            if (
                strncasecmp($path, $candidate, strlen($candidate)) === 0
                && (strlen($path) === strlen($candidate) || $path[strlen($candidate)] === '/')
            ) {
                $path = substr($path, strlen($candidate));
                break;
            }
        }

        return $path !== '' ? (rtrim($path, '/') ?: '/') : '/';
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$normalized] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }
}
