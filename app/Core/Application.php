<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;
use Throwable;

class Application
{
    public function __construct(
        private readonly string $basePath,
        private readonly Container $container,
        private readonly Router $router
    ) {
        $GLOBALS['app'] = $this;
        date_default_timezone_set((string) config('app.timezone', 'UTC'));
    }

    public function basePath(string $path = ''): string
    {
        return $path === '' ? $this->basePath : $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function run(Request $request): void
    {
        $this->handle($request)->send();
    }

    public function handle(Request $request): Response
    {
        try {
            [$route, $params] = $this->router->dispatch($request);
            $coreRunner = fn (Request $incoming): Response => $this->executeRoute($route->action, $params, $incoming);

            $middlewares = array_merge(
                config('app.middleware', []),
                $route->middleware
            );

            $runner = array_reduce(
                array_reverse($middlewares),
                function (callable $next, string $middleware): callable {
                    return function (Request $incoming) use ($middleware, $next): Response {
                        $instance = $this->container->make($middleware);
                        if (!$instance instanceof MiddlewareInterface) {
                            throw new \RuntimeException("Middleware [$middleware] must implement MiddlewareInterface.");
                        }
                        return $instance->handle($incoming, $next);
                    };
                },
                $coreRunner
            );

            $response = $runner($request);
            foreach ((array) config('app.security_headers', []) as $name => $value) {
                $response->setHeader((string) $name, (string) $value);
            }

            return $response;
        } catch (HttpException $e) {
            return $this->renderError($e->getStatusCode(), $e->getMessage());
        } catch (Throwable $e) {
            return $this->renderError(500, config('app.debug') ? $e->getMessage() : 'Server Error');
        }
    }

    private function executeRoute(mixed $action, array $routeParams, Request $request): Response
    {
        $result = $this->container->call($action, array_merge($routeParams, ['request' => $request]));

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return response((string) $result);
    }

    private function renderError(int $status, string $message): Response
    {
        $viewFile = $this->basePath('views/errors/' . $status . '.php');
        if (is_file($viewFile)) {
            http_response_code($status);
            ob_start();
            $errorMessage = $message;
            require $viewFile;
            $content = (string) ob_get_clean();
            return new Response($content, $status);
        }

        return new Response($message, $status);
    }
}
