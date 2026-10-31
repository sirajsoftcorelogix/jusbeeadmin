<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\AppUser;

class AppUserAdminController extends Controller
{
    public function index(Request $request, Csrf $csrf, AppUser $users): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $editing = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editing = $users->find($editId);
            if ($editing === null) {
                throw new HttpException(404, 'App user not found.');
            }
        }

        $notice = match ((string) $request->query('notice', '')) {
            'created' => 'App user created.',
            'saved' => 'App user updated.',
            'deleted' => 'App user deleted.',
            'status' => 'App user status changed.',
            default => null,
        };

        return $this->renderIndex($csrf, $users, [
            'editingUser' => $editing,
            'search' => $search,
            'status' => $status,
            'notice' => $notice,
        ]);
    }

    public function store(Request $request, Csrf $csrf, AppUser $users): Response
    {
        $input = $this->input($request);
        $error = $this->validateInput($input, $users, null, true);
        if ($error !== null) {
            return $this->renderIndex($csrf, $users, ['error' => $error, 'old' => $input]);
        }

        $id = $users->create(
            $input['first_name'],
            $input['last_name'],
            $input['email'],
            $input['phone'],
            password_hash($input['password'], PASSWORD_DEFAULT)
        );
        if ($input['status'] !== 'active') {
            $users->update($id, ['status' => $input['status']]);
        }

        return $this->redirect($this->url() . '?notice=created');
    }

    public function update(string $id, Request $request, Csrf $csrf, AppUser $users): Response
    {
        $userId = $this->userId($id);
        $user = $this->findOrFail($users, $userId);

        $input = $this->input($request);
        $error = $this->validateInput($input, $users, $userId, false);
        if ($error !== null) {
            return $this->renderIndex($csrf, $users, ['editingUser' => $user, 'error' => $error, 'old' => $input]);
        }

        $fields = [
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'status' => $input['status'],
        ];
        if ($input['password'] !== '') {
            $fields['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        $users->update($userId, $fields);

        // Deactivated users or users with a new password lose their API sessions.
        if ($input['status'] !== 'active' || $input['password'] !== '') {
            $this->revokeTokens($userId);
        }

        return $this->redirect($this->url() . '?notice=saved');
    }

    public function setStatus(string $id, Request $request, AppUser $users): Response
    {
        $userId = $this->userId($id);
        $user = $this->findOrFail($users, $userId);
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

        $users->update($userId, ['status' => $newStatus]);
        if ($newStatus === 'inactive') {
            $this->revokeTokens($userId);
        }

        return $this->redirect($this->url() . '?notice=status');
    }

    public function destroy(string $id, AppUser $users): Response
    {
        $userId = $this->userId($id);
        $this->findOrFail($users, $userId);
        $users->delete($userId);

        return $this->redirect($this->url() . '?notice=deleted');
    }

    private function revokeTokens(int $userId): void
    {
        app(\App\Models\AppUserToken::class)->revokeAllForUser($userId);
    }

    /** @return array{first_name:string,last_name:string,email:string,phone:string,password:string,status:string} */
    private function input(Request $request): array
    {
        $text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

        return [
            'first_name' => $text($request->input('first_name')),
            'last_name' => $text($request->input('last_name')),
            'email' => strtolower($text($request->input('email'))),
            'phone' => preg_replace('/[\s\-().]/', '', $text($request->input('phone'))) ?? '',
            'password' => is_string($request->input('password')) ? $request->input('password') : '',
            'status' => $text($request->input('status')),
        ];
    }

    private function validateInput(array $input, AppUser $users, ?int $excludeId, bool $passwordRequired): ?string
    {
        $firstNameLength = mb_strlen($input['first_name']);
        if ($firstNameLength < 1 || $firstNameLength > 120) {
            return 'First name must be 1 to 120 characters.';
        }
        if (mb_strlen($input['last_name']) > 120) {
            return 'Last name must be 120 characters or fewer.';
        }
        if (filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false || strlen($input['email']) > 190) {
            return 'Provide a valid email address.';
        }
        if ($users->emailExists($input['email'], $excludeId)) {
            return 'This email is already registered.';
        }
        if (preg_match('/^\+?[0-9]{7,15}$/', $input['phone']) !== 1) {
            return 'Provide a valid phone number (7 to 15 digits).';
        }
        if ($users->phoneExists($input['phone'], $excludeId)) {
            return 'This phone number is already registered.';
        }
        if (!in_array($input['status'], ['active', 'inactive'], true)) {
            return 'Select a valid status.';
        }
        if ($passwordRequired || $input['password'] !== '') {
            $passwordLength = strlen($input['password']);
            if ($passwordLength < 8 || $passwordLength > 72) {
                return 'Password must be 8 to 72 characters.';
            }
        }

        return null;
    }

    private function renderIndex(Csrf $csrf, AppUser $users, array $options = []): Response
    {
        $search = (string) ($options['search'] ?? '');
        $status = (string) ($options['status'] ?? 'all');
        $old = $options['old'] ?? [];
        unset($old['password']);

        return $this->view('app_users.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'appUsersUrl' => $this->url(),
            'csrfField' => $csrf->field(),
            'items' => $users->search($search, $status),
            'totalItems' => $users->countAll(),
            'editingUser' => $options['editingUser'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $old,
            'search' => $search,
            'status' => $status,
        ]);
    }

    private function findOrFail(AppUser $users, int $id): array
    {
        $user = $users->find($id);
        if ($user === null) {
            throw new HttpException(404, 'App user not found.');
        }

        return $user;
    }

    private function url(): string
    {
        return rtrim((string) config('app.url'), '/') . '/app-users';
    }

    private function userId(string $id): int
    {
        $userId = filter_var($id, FILTER_VALIDATE_INT);
        if ($userId === false || $userId < 1) {
            throw new HttpException(404, 'App user not found.');
        }

        return $userId;
    }
}
