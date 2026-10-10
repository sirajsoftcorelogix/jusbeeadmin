<?php

declare(strict_types=1);

namespace App\Core;

class Route
{
    public array $middleware = [];

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly mixed $action
    ) {
    }

    public function middleware(string|array $middleware): self
    {
        $items = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_values(array_unique(array_merge($this->middleware, $items)));
        return $this;
    }
}
