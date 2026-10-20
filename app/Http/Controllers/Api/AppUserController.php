<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\AppUser;
use App\Models\AppUserToken;

class AppUserController extends Controller
{
    public function register(Request $request, AppUser $users, AppUserToken $tokens): Response
    {
        // Debug: print all request data        
        $input = $this->fields($request);
        $password = $this->string($request->input('password'));

        $errors = $this->validateProfile($input, $users, null);
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'Password must be 8 to 72 characters.';
        }
        if ($errors !== []) {
            return $this->fail('Validation failed.', 422, $errors);
        }

        $id = $users->create($input['name'], $input['email'], $input['phone'], password_hash($password, PASSWORD_DEFAULT));
        $issued = $tokens->issue($id, $this->device($request));

        return $this->json([
            'success' => true,
            'message' => 'Registration successful.',
            'token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expires_at'],
            'user' => $this->present($users->find($id)),
        ], 201);
    }

    public function login(Request $request, AppUser $users, AppUserToken $tokens): Response
    {
        $login = strtolower($this->string($request->input('login', $request->input('email'))));
        $password = $this->string($request->input('password'));
        if ($login === '' || $password === '') {
            return $this->fail('Validation failed.', 422, ['login' => 'Email or phone and password are required.']);
        }

        $user = str_contains($login, '@')
            ? $users->findByEmail($login)
            : $users->findByPhone($this->normalizePhone($login));

        // Always run a hash check so response time does not reveal whether the account exists.
        $hash = $user['password'] ?? '$2y$10$usesomesillystringforsaltuOWmW0VvBwhYdZpV7HbmaLgm0n4eq';
        if (!password_verify($password, $hash) || $user === null) {
            return $this->fail('Invalid credentials.', 401);
        }
        if ($user['status'] !== 'active') {
            return $this->fail('This account is inactive.', 403);
        }

        $users->touchLogin((int) $user['id']);
        $issued = $tokens->issue((int) $user['id'], $this->device($request));

        return $this->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expires_at'],
            'user' => $this->present($users->find((int) $user['id'])),
        ]);
    }

    public function profile(Request $request): Response
    {
        return $this->json(['success' => true, 'user' => $this->present($request->attribute('api_user'))]);
    }

    public function logout(Request $request, AppUserToken $tokens): Response
    {
        $tokens->revoke((int) $request->attribute('api_token')['id']);

        return $this->json(['success' => true, 'message' => 'Logged out.']);
    }

    public function logoutAll(Request $request, AppUserToken $tokens): Response
    {
        $tokens->revokeAllForUser((int) $request->attribute('api_user')['id']);

        return $this->json(['success' => true, 'message' => 'Logged out from all devices.']);
    }

    public function update(Request $request, AppUser $users, AppUserToken $tokens): Response
    {
        $user = $request->attribute('api_user');

        $id = (int) $user['id'];
        $input = [];
        foreach (['name', 'email', 'phone'] as $field) {
            $input[$field] = $request->input($field) !== null
                ? $this->string($request->input($field))
                : (string) $user[$field];
        }
        $input['email'] = strtolower($input['email']);
        $input['phone'] = $this->normalizePhone($input['phone']);

        $errors = $this->validateProfile($input, $users, $id);
        $changes = [];
        foreach (['name', 'email', 'phone'] as $field) {
            if ($input[$field] !== $user[$field]) {
                $changes[$field] = $input[$field];
            }
        }

        if ($request->input('password') !== null) {
            $newPassword = $this->string($request->input('password'));
            if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
                $errors['password'] = 'Password must be 8 to 72 characters.';
            } elseif (!password_verify($this->string($request->input('current_password')), (string) $user['password'])) {
                $errors['current_password'] = 'Current password is incorrect.';
            } else {
                $changes['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        if ($errors !== []) {
            return $this->fail('Validation failed.', 422, $errors);
        }

        $users->update($id, $changes);
        if (isset($changes['password'])) {
            $tokens->revokeAllForUser($id, (int) $request->attribute('api_token')['id']);
        }

        return $this->json([
            'success' => true,
            'message' => 'Profile updated.',
            'user' => $this->present($users->find($id)),
        ]);
    }

    /** @return array{name:string,email:string,phone:string} */
    private function fields(Request $request): array
    {
        return [
            'name' => $this->string($request->input('name')),
            'email' => strtolower($this->string($request->input('email'))),
            'phone' => $this->normalizePhone($this->string($request->input('phone'))),
        ];
    }

    /** @return array<string,string> */
    private function validateProfile(array $input, AppUser $users, ?int $excludeId): array
    {
        $errors = [];

        $length = mb_strlen($input['name']);
        if ($length < 2 || $length > 120) {
            $errors['name'] = 'Name must be 2 to 120 characters.';
        }

        if (filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false || strlen($input['email']) > 190) {
            $errors['email'] = 'Provide a valid email address.';
        } elseif ($users->emailExists($input['email'], $excludeId)) {
            $errors['email'] = 'This email is already registered.';
        }

        if (preg_match('/^\+?[0-9]{7,15}$/', $input['phone']) !== 1) {
            $errors['phone'] = 'Provide a valid phone number (7 to 15 digits).';
        } elseif ($users->phoneExists($input['phone'], $excludeId)) {
            $errors['phone'] = 'This phone number is already registered.';
        }

        return $errors;
    }

    private function present(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'status' => $user['status'],
            'created_at' => $user['created_at'],
            'modified_at' => $user['modified_at'],
        ];
    }

    private function fail(string $message, int $status, array $errors = []): Response
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->json($body, $status);
    }

    private function device(Request $request): ?string
    {
        $device = $request->input('device_name');
        if (is_string($device) && trim($device) !== '') {
            return trim($device);
        }
        $agent = $request->header('User-Agent');

        return is_string($agent) && $agent !== '' ? $agent : null;
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s\-().]/', '', $phone) ?? $phone;
    }
}
