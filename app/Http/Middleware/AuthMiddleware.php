<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly User $users
    )
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->session->get('admin_authenticated') !== true) {
            return Response::redirect(rtrim((string) config('app.url'), '/') . '/login');
        }

        $userId = filter_var($this->session->get('admin_id'), FILTER_VALIDATE_INT);
        $user = $userId !== false && $userId > 0 ? $this->users->find($userId) : null;
        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->session->forget('admin_authenticated');
            $this->session->forget('admin_id');
            $this->session->forget('admin_name');
            $this->session->forget('admin_role');

            return Response::redirect(rtrim((string) config('app.url'), '/') . '/login');
        }

        $this->session->put('admin_name', (string) $user['name']);
        $this->session->put('admin_role', (string) $user['role']);

        return $next($request);
    }
}
