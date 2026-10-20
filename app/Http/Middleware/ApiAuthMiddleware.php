<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Models\AppUser;
use App\Models\AppUserToken;

class ApiAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AppUserToken $tokens,
        private readonly AppUser $users
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $header = trim((string) $request->header('Authorization', ''));
        if (preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', $header, $matches) !== 1) {
            return $this->unauthorized('Missing or malformed bearer token.');
        }

        $token = $this->tokens->findValid(strtolower($matches[1]));
        $user = $token !== null ? $this->users->find((int) $token['user_id']) : null;
        if ($token === null || $user === null) {
            return $this->unauthorized('Invalid or expired token.');
        }
        if ($user['status'] !== 'active') {
            return Response::json(['success' => false, 'message' => 'This account is inactive.'], 403);
        }

        $this->tokens->touch((int) $token['id']);
        $request->setAttribute('api_user', $user);
        $request->setAttribute('api_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = Response::json(['success' => false, 'message' => $message], 401);
        $response->setHeader('WWW-Authenticate', 'Bearer');

        return $response;
    }
}
