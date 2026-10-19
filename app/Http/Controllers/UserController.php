<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

class UserController extends Controller
{
    private const ROLES = [
        'admin' => 'Admin',
        'operations' => 'Operations',
    ];

    public function index(Request $request, Csrf $csrf, User $users): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $editingUser = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editingUser = $users->find($editId);
            if ($editingUser === null) {
                throw new HttpException(404, 'User not found.');
            }
        }

        return $this->renderIndex($csrf, $users, [
            'editingUser' => $editingUser,
            'search' => $search,
            'status' => $status,
            'notice' => match ((string) $request->query('notice', '')) {
                'created' => 'User created successfully.',
                'saved' => 'User details updated successfully.',
                'activated' => 'User account activated.',
                'deactivated' => 'User account deactivated.',
                default => null,
            },
        ]);
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
            return $this->renderIndex($csrf, $users, [
                'error' => 'Select a valid user role.',
                'old' => $data,
            ]);
        }

        $email = strtolower(trim((string) $data['email']));
        if ($users->findByEmail($email) !== null) {
            return $this->renderIndex($csrf, $users, [
                'error' => 'A user with this email address already exists.',
                'old' => $data,
            ]);
        }

        $users->create(
            trim((string) $data['name']),
            $email,
            (string) $data['password'],
            $role
        );

        return $this->redirect($this->usersUrl() . '?notice=created');
    }

    public function update(string $id, Request $request, Csrf $csrf, Session $session, User $users): Response
    {
        $userId = $this->userId($id);
        $user = $users->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $data = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:191',
            'role' => 'required',
            'password' => 'max:255',
        ]);
        $password = trim((string) ($data['password'] ?? ''));
        if ($password !== '' && mb_strlen($password) < 8) {
            return $this->renderIndex($csrf, $users, [
                'editingUser' => $user,
                'error' => 'A new password must be at least 8 characters.',
                'old' => $data,
            ]);
        }

        $role = (string) $data['role'];
        if (!array_key_exists($role, self::ROLES)) {
            return $this->renderIndex($csrf, $users, [
                'editingUser' => $user,
                'error' => 'Select a valid user role.',
                'old' => $data,
            ]);
        }

        if ((int) $session->get('admin_id') === $userId && $role !== 'admin') {
            return $this->renderIndex($csrf, $users, [
                'editingUser' => $user,
                'error' => 'You cannot remove your own administrator role.',
                'old' => $data,
            ]);
        }

        if ($user['role'] === 'admin' && (int) $user['is_active'] === 1 && $role !== 'admin' && $users->activeAdminCount() <= 1) {
            return $this->renderIndex($csrf, $users, [
                'editingUser' => $user,
                'error' => 'At least one active administrator must remain.',
                'old' => $data,
            ]);
        }

        $email = strtolower(trim((string) $data['email']));
        $existingUser = $users->findByEmail($email);
        if ($existingUser !== null && (int) $existingUser['id'] !== $userId) {
            return $this->renderIndex($csrf, $users, [
                'editingUser' => $user,
                'error' => 'A user with this email address already exists.',
                'old' => $data,
            ]);
        }

        $users->updateDetails(
            $userId,
            trim((string) $data['name']),
            $email,
            $role,
            $password !== '' ? $password : null
        );

        return $this->redirect($this->usersUrl() . '?notice=saved');
    }

    public function setStatus(string $id, Request $request, Session $session, User $users): Response
    {
        $userId = $this->userId($id);
        $user = $users->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $activeValue = $request->input('is_active');
        if (!in_array($activeValue, ['0', '1'], true)) {
            throw new HttpException(422, 'Select a valid account status.');
        }
        $active = $activeValue === '1';
        if (!$active && (int) $session->get('admin_id') === $userId) {
            throw new HttpException(422, 'You cannot deactivate your own account.');
        }

        if (!$active && $user['role'] === 'admin' && (int) $user['is_active'] === 1 && $users->activeAdminCount() <= 1) {
            throw new HttpException(422, 'At least one active administrator must remain.');
        }

        $users->setActive($userId, $active);
        $notice = $active ? 'activated' : 'deactivated';

        return $this->redirect($this->usersUrl() . '?notice=' . $notice);
    }

    private function renderIndex(Csrf $csrf, User $users, array $options = []): Response
    {
        $search = (string) ($options['search'] ?? '');
        $status = (string) ($options['status'] ?? 'all');
        $editingUser = $options['editingUser'] ?? null;

        return $this->view('users.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'usersUrl' => $this->usersUrl(),
            'csrfField' => $csrf->field(),
            'users' => $users->search($search, $status),
            'totalUsers' => $users->countAll(),
            'activeAdminCount' => $users->activeAdminCount(),
            'currentUserId' => (int) app(Session::class)->get('admin_id'),
            'roles' => self::ROLES,
            'editingUser' => $editingUser,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
            'search' => $search,
            'status' => $status,
        ]);
    }

    private function usersUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/users';
    }

    private function userId(string $id): int
    {
        $userId = filter_var($id, FILTER_VALIDATE_INT);
        if ($userId === false || $userId < 1) {
            throw new HttpException(404, 'User not found.');
        }

        return $userId;
    }
}
