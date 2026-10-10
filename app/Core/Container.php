<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, Closure|string $concrete): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => false];
    }

    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => true];
    }

    public function make(string $abstract): mixed
    {
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        $object = $concrete instanceof Closure ? $concrete($this) : $this->build($concrete);

        if (($this->bindings[$abstract]['shared'] ?? false) === true) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    public function call(mixed $callable, array $parameters = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '@')) {
            [$class, $method] = explode('@', $callable, 2);
            $instance = $this->make($class);
            return $this->call([$instance, $method], $parameters);
        }

        if (is_array($callable) && isset($callable[0], $callable[1]) && is_string($callable[0])) {
            $callable[0] = $this->make($callable[0]);
        }

        $reflection = match (true) {
            is_array($callable) => new ReflectionMethod($callable[0], (string) $callable[1]),
            $callable instanceof Closure => new ReflectionFunction($callable),
            is_object($callable) && method_exists($callable, '__invoke') => new ReflectionMethod($callable, '__invoke'),
            is_string($callable) => new ReflectionFunction($callable),
            default => throw new RuntimeException('Unsupported callable provided.')
        };

        $resolved = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $parameters)) {
                $resolved[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $resolved[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Unable to resolve parameter [$name].");
        }

        return $callable(...$resolved);
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Class [$class] does not exist.");
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class [$class] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Unable to instantiate [$class] because [{$parameter->getName()}] cannot be resolved.");
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
