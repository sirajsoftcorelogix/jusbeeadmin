<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Counsellor;

class CounsellorController extends Controller
{
    private const MAX_THUMBNAIL_BYTES = 104857600;

    public function index(Request $request, Csrf $csrf, Counsellor $counsellors): Response
    {
        $editing = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editing = $counsellors->find($editId);
            if ($editing === null) {
                throw new HttpException(404, 'Counsellor not found.');
            }
        }

        return $this->renderIndex($request, $csrf, $counsellors, [
            'editingCounsellor' => $editing,
            'notice' => match ((string) $request->query('notice', '')) {
                'created' => 'Counsellor created.',
                'saved' => 'Counsellor updated.',
                'deleted' => 'Counsellor deleted.',
                default => null,
            },
        ]);
    }

    public function store(Request $request, Csrf $csrf, Counsellor $counsellors, Session $session): Response
    {
        $input = $this->input($request);
        $error = $this->validateInput($input);
        if ($error !== null) {
            return $this->renderIndex($request, $csrf, $counsellors, ['error' => $error, 'old' => $input]);
        }

        [$thumbnail, $uploadError] = $this->handleThumbnail($request, null);
        if ($uploadError !== null) {
            return $this->renderIndex($request, $csrf, $counsellors, [
                'error' => $uploadError,
                'old' => $input,
            ]);
        }

        $input['profile_thumbnail'] = $thumbnail;
        $input['updated_by'] = (int) $session->get('admin_id');
        try {
            $counsellors->create($input);
        } catch (\Throwable $error) {
            $this->deleteThumbnail($thumbnail);
            throw $error;
        }

        return $this->redirect($this->url() . '?notice=created');
    }

    public function update(
        string $id,
        Request $request,
        Csrf $csrf,
        Counsellor $counsellors,
        Session $session
    ): Response {
        $counsellorId = $this->counsellorId($id);
        $counsellor = $counsellors->find($counsellorId);
        if ($counsellor === null) {
            throw new HttpException(404, 'Counsellor not found.');
        }

        $input = $this->input($request);
        $error = $this->validateInput($input);
        if ($error !== null) {
            return $this->renderIndex($request, $csrf, $counsellors, [
                'editingCounsellor' => $counsellor,
                'error' => $error,
                'old' => $input,
            ]);
        }

        [$thumbnail, $uploadError] = $this->handleThumbnail($request, $counsellor['profile_thumbnail']);
        if ($uploadError !== null) {
            return $this->renderIndex($request, $csrf, $counsellors, [
                'editingCounsellor' => $counsellor,
                'error' => $uploadError,
                'old' => $input,
            ]);
        }

        $input['profile_thumbnail'] = $thumbnail;
        $input['updated_by'] = (int) $session->get('admin_id');
        try {
            $counsellors->update($counsellorId, $input);
        } catch (\Throwable $error) {
            if ($thumbnail !== $counsellor['profile_thumbnail']) {
                $this->deleteThumbnail($thumbnail);
            }
            throw $error;
        }
        if ($thumbnail !== $counsellor['profile_thumbnail']) {
            $this->deleteThumbnail($counsellor['profile_thumbnail']);
        }

        return $this->redirect($this->url() . '?notice=saved');
    }

    public function destroy(string $id, Counsellor $counsellors): Response
    {
        $counsellorId = $this->counsellorId($id);
        $counsellor = $counsellors->find($counsellorId);
        if ($counsellor === null) {
            throw new HttpException(404, 'Counsellor not found.');
        }
        $counsellors->delete($counsellorId);
        $this->deleteThumbnail($counsellor['profile_thumbnail']);

        return $this->redirect($this->url() . '?notice=deleted');
    }

    private function input(Request $request): array
    {
        $text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';
        $fee = static function (mixed $value): string {
            if (is_float($value) || is_int($value) || is_string($value)) {
                return trim((string) $value);
            }

            return '';
        };

        return [
            'consellor_name' => $text($request->input('consellor_name')),
            'qualifications' => $text($request->input('qualifications')) ?: null,
            'email' => strtolower($text($request->input('email'))) ?: null,
            'phone' => $text($request->input('phone')) ?: null,
            'verifies' => $text($request->input('verifies')),
            'biography' => $text($request->input('biography')) ?: null,
            'expertise' => $text($request->input('expertise')) ?: null,
            'standard_fee' => $fee($request->input('standard_fee')),
            'discovery_fee' => $fee($request->input('discovery_fee')),
        ];
    }

    private function validateInput(array $input): ?string
    {
        if ($input['consellor_name'] === '' || mb_strlen($input['consellor_name']) > 255) {
            return 'Counsellor name is required and must be 255 characters or fewer.';
        }
        if ($input['email'] !== null && (strlen($input['email']) > 190 || filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false)) {
            return 'Provide a valid email address of at most 190 characters, or leave it blank.';
        }
        if ($input['phone'] !== null && (strlen($input['phone']) > 30 || preg_match('/^\+?[0-9\s().-]{7,30}$/', $input['phone']) !== 1)) {
            return 'Provide a valid phone number of at most 30 characters, or leave it blank.';
        }
        if (!in_array($input['verifies'], ['Y', 'N'], true)) {
            return 'Select Y or N for verification.';
        }
        foreach (['standard_fee', 'discovery_fee'] as $field) {
            if (!is_numeric($input[$field]) || (float) $input[$field] < 0 || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $input[$field])) {
                return ucfirst(str_replace('_', ' ', $field)) . ' must be a non-negative amount with up to 2 decimal places.';
            }
        }

        return null;
    }

    /** @return array{0:?string,1:?string} */
    private function handleThumbnail(Request $request, ?string $existing): array
    {
        $file = $request->file('profile_thumbnail');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($request->input('remove_profile_thumbnail') === '1') {
                return [null, null];
            }

            return [$existing, null];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            return [$existing, 'Profile thumbnail exceeds the server upload limit.'];
        }
        if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return [$existing, 'Profile thumbnail upload failed.'];
        }
        if ((int) $file['size'] > self::MAX_THUMBNAIL_BYTES) {
            return [$existing, 'Profile thumbnail must be 100 MB or smaller.'];
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $valid = ($extension === 'webp' && $mime === 'image/webp')
            || (in_array($extension, ['jpg', 'jpeg'], true) && $mime === 'image/jpeg');
        if (!$valid) {
            return [$existing, 'Profile thumbnail must be a WebP or JPEG image.'];
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/counsellors';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return [$existing, 'Unable to create counsellor thumbnail directory.'];
        }
        $name = 'profile-' . bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            return [$existing, 'Profile thumbnail upload failed.'];
        }

        return ['uploads/counsellors/' . $name, null];
    }

    private function deleteThumbnail(mixed $path): void
    {
        if (!is_string($path) || preg_match('#^uploads/counsellors/[A-Za-z0-9._-]+$#', $path) !== 1) {
            return;
        }
        $fullPath = dirname(__DIR__, 3) . '/public/' . $path;
        if (is_file($fullPath) && !unlink($fullPath)) {
            error_log("Unable to remove counsellor thumbnail: {$fullPath}");
        }
    }

    private function renderIndex(Request $request, Csrf $csrf, Counsellor $counsellors, array $options = []): Response
    {
        $search = trim((string) $request->query('search', ''));

        return $this->view('counsellors.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'counsellorsUrl' => $this->url(),
            'csrfField' => $csrf->field(),
            'items' => $counsellors->search($search),
            'totalItems' => $counsellors->countAll(),
            'editingCounsellor' => $options['editingCounsellor'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
            'search' => $search,
        ]);
    }

    private function url(): string
    {
        return rtrim((string) config('app.url'), '/') . '/counsellors';
    }

    private function counsellorId(string $id): int
    {
        $counsellorId = filter_var($id, FILTER_VALIDATE_INT);
        if ($counsellorId === false || $counsellorId < 1) {
            throw new HttpException(404, 'Counsellor not found.');
        }

        return $counsellorId;
    }
}
