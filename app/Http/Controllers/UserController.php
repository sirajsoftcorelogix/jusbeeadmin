<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class UserController extends Controller
{
    private const ROLES = [
        'admin' => 'Admin',
        'operations' => 'Operations',
    ];

    public function index(Csrf $csrf, User $users): Response
    {
        return $this->renderIndex($csrf, $users);
    }

    public function store(Request $request, Csrf $csrf, User $users): Response
    {
        $data = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:191',
            'password' => 'required|min:8|max:255',
            'role' => 'required',
        ]);

        $role = (string) $data['role'];
        if (!array_key_exists($role, self::ROLES)) {
            return $this->renderIndex($csrf, $users, 'Select a valid user role.', $data);
        }

        $email = strtolower(trim((string) $data['email']));
        if ($users->findByEmail($email) !== null) {
            return $this->renderIndex($csrf, $users, 'A user with this email address already exists.', $data);
        }

        $users->create(
            trim((string) $data['name']),
            $email,
            (string) $data['password'],
            $role
        );

        return $this->redirect(rtrim((string) config('app.url'), '/') . '/users');
    }

    private function renderIndex(Csrf $csrf, User $users, ?string $error = null, array $old = []): Response
    {
        return $this->view('users.index', [
            'appName' => (string) config('app.name'),
            'csrfField' => $csrf->field(),
            'users' => $users->all(),
            'roles' => self::ROLES,
            'error' => $error,
            'old' => $old,
        ]);
    }
}
