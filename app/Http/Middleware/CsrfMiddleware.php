<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Csrf $csrf)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token');
            if (!is_string($token) || !$this->csrf->verify($token)) {
                throw new HttpException(419, 'Invalid CSRF token');
            }
        }

        return $next($request);
    }
}
