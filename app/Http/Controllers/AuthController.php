<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(Csrf $csrf, Session $session): Response
    {
        if ($session->get('admin_authenticated') === true) {
            return $this->redirectToDashboard();
        }

        return $this->view('auth.login', [
            'appName' => (string) config('app.name'),
            'csrfField' => $csrf->field(),
        ]);
    }

    public function login(Request $request, Session $session, User $users): Response
    {
        $credentials = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:1',
        ]);

        $email = strtolower(trim((string) $credentials['email']));
        $password = (string) $credentials['password'];
        $user = $users->findByEmail($email);

        if (
            $user === null
            || (int) $user['is_active'] !== 1
            || !password_verify($password, (string) $user['password'])
        ) {
            return $this->view('auth.login', [
                'appName' => (string) config('app.name'),
                'csrfField' => app(Csrf::class)->field(),
                'error' => 'The email or password is incorrect.',
                'email' => $email,
            ]);
        }

        $session->regenerate();
        $session->put('admin_authenticated', true);
        $session->put('admin_id', (int) $user['id']);
        $session->put('admin_name', (string) $user['name']);
        $session->put('admin_role', (string) $user['role']);

        return $this->redirectToDashboard();
    }

    public function logout(Session $session): Response
    {
        $session->forget('admin_authenticated');
        $session->forget('admin_id');
        $session->forget('admin_name');
        $session->forget('admin_role');
        $session->regenerate();

        return Response::redirect(rtrim((string) config('app.url'), '/') . '/login');
    }

    private function redirectToDashboard(): Response
    {
        return Response::redirect(rtrim((string) config('app.url'), '/') . '/');
    }
}
