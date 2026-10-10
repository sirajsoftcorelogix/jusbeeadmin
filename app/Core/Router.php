<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;

class Router
{
    /** @var Route[] */
    private array $routes = [];

    public function get(string $uri, mixed $action): Route
    {
        return $this->add('GET', $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->add('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->add('PUT', $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->add('PATCH', $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->add('DELETE', $uri, $action);
    }

    public function add(string $method, string $uri, mixed $action): Route
    {
        $route = new Route(strtoupper($method), $this->normalizeUri($uri), $action);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @return array{Route,array<string,string>}
     */
    public function dispatch(Request $request): array
    {
        $requestMethod = $request->method();
        $requestPath = $request->path();

        foreach ($this->routes as $route) {
            if ($route->method !== $requestMethod) {
                continue;
            }

            $paramNames = [];
            $pattern = preg_replace_callback(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                static function (array $matches) use (&$paramNames): string {
                    $paramNames[] = $matches[1];
                    return '([^/]+)';
                },
                $route->uri
            );

            if ($pattern === null) {
                continue;
            }

            if (preg_match('#^' . $pattern . '$#', $requestPath, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($paramNames as $idx => $name) {
                $params[$name] = urldecode($matches[$idx] ?? '');
            }

            return [$route, $params];
        }

        throw new HttpException(404, 'Page Not Found');
    }

    private function normalizeUri(string $uri): string
    {
        $trimmed = '/' . trim($uri, '/');
        return $trimmed === '//' ? '/' : $trimmed;
    }
}
