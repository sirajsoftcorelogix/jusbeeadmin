<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Session $session)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->session->get('admin_authenticated') !== true) {
            return Response::redirect(rtrim((string) config('app.url'), '/') . '/login');
        }

        return $next($request);
    }
}
