<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Library;

class LibraryController extends Controller
{
    private const MAX_UPLOAD_BYTES = 104857600;

    public function index(Request $request, Csrf $csrf, Library $library): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $editingItem = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editingItem = $library->find($editId);
            if ($editingItem === null) {
                throw new HttpException(404, 'Library item not found.');
            }
        }

        $notice = match ((string) $request->query('notice', '')) {
            'created' => 'Library item created.',
            'saved' => 'Library item updated.',
            'deleted' => 'Library item deleted.',
            default => null,
        };

        return $this->renderIndex($csrf, $library, [
            'editingItem' => $editingItem,
            'search' => $search,
            'status' => $status,
            'notice' => $notice,
        ]);
    }

    public function store(Request $request, Csrf $csrf, Library $library): Response
    {
        $input = $this->input($request);
        $error = $this->validateInput($input, $library);
        if ($error !== null) {
            return $this->renderIndex($csrf, $library, ['error' => $error, 'old' => $input]);
        }

        [$files, $uploadError] = $this->handleUploads($request, []);
        $error = $uploadError ?? $this->requireMedia($input['media'] + $files);
        if ($error !== null) {
            $this->deleteFiles($files);

            return $this->renderIndex($csrf, $library, ['error' => $error, 'old' => $input]);
        }

        $library->create($input['slug'], $input['media'] + $files, $input['status']);

        return $this->redirect($this->libraryUrl() . '?notice=created');
    }

    public function update(string $id, Request $request, Csrf $csrf, Library $library): Response
    {
        $itemId = $this->itemId($id);
        $item = $library->find($itemId);
        if ($item === null) {
            throw new HttpException(404, 'Library item not found.');
        }

        $input = $this->input($request);
        $error = $this->validateInput($input, $library, $itemId);
        if ($error !== null) {
            return $this->renderIndex($csrf, $library, [
                'editingItem' => $item,
                'error' => $error,
                'old' => $input,
            ]);
        }

        $existing = $this->fileValues($item);
        [$files, $uploadError] = $this->handleUploads($request, $existing);
        $error = $uploadError ?? $this->requireMedia($input['media'] + $files);
        if ($error !== null) {
            $this->deleteFiles(array_diff_assoc($files, $existing));

            return $this->renderIndex($csrf, $library, [
                'editingItem' => $item,
                'error' => $error,
                'old' => $input,
            ]);
        }

        $library->update($itemId, $input['slug'], $input['media'] + $files, $input['status']);
        $this->deleteFiles(array_diff_assoc($existing, $files));

        return $this->redirect($this->libraryUrl() . '?notice=saved');
    }

    public function destroy(string $id, Library $library): Response
    {
        $itemId = $this->itemId($id);
        if ($library->find($itemId) === null) {
            throw new HttpException(404, 'Library item not found.');
        }

        $item = $library->find($itemId);
        $library->delete($itemId);
        $this->deleteFiles($this->fileValues($item));

        return $this->redirect($this->libraryUrl() . '?notice=deleted');
    }

    /**
     * @return array{slug:string,status:string,media:array<string,?string>}
     */
    private function input(Request $request): array
    {
        $text = static function (mixed $value): string {
            return is_string($value) ? trim($value) : '';
        };

        $media = [];
        foreach (Library::URL_FIELDS as $field) {
            $value = $text($request->input($field));
            $media[$field] = $value !== '' ? $value : null;
        }

        return [
            'slug' => strtolower($text($request->input('slug'))),
            'status' => $text($request->input('status')),
            'media' => $media,
        ];
    }

    private function validateInput(array $input, Library $library, ?int $excludeId = null): ?string
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input['slug']) !== 1 || strlen($input['slug']) > 160) {
            return 'Slug may contain only lowercase letters, numbers, and hyphens (max 160 characters).';
        }

        if ($library->slugExists($input['slug'], $excludeId)) {
            return 'An item with this slug already exists.';
        }

        if (!in_array($input['status'], ['active', 'inactive'], true)) {
            return 'Select a valid status.';
        }

        foreach ($input['media'] as $field => $value) {
            if ($value !== null && (strlen($value) > 500 || !$this->isHttpUrl($value))) {
                return ucfirst($field) . ' must be a valid http(s) URL of at most 500 characters.';
            }
        }

        return null;
    }

    private function requireMedia(array $media): ?string
    {
        foreach ($media as $value) {
            if ($value !== null && $value !== '') {
                return null;
            }
        }

        return 'Provide at least one of YouTube, audio, video, link, or image.';
    }

    /**
     * @param array<string,?string> $existing
     * @return array{0:array<string,?string>,1:?string}
     */
    private function handleUploads(Request $request, array $existing): array
    {
        $rules = [
            'audio' => ['exts' => ['mp3', 'wav', 'ogg', 'm4a', 'aac'], 'prefix' => 'audio/'],
            'video' => ['exts' => ['mp4', 'webm', 'ogg', 'mov'], 'prefix' => 'video/'],
            'image' => ['exts' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'prefix' => 'image/'],
        ];
        $result = $existing + ['audio' => null, 'video' => null, 'image' => null];

        foreach ($rules as $field => $rule) {
            if ($request->input('remove_' . $field) === '1') {
                $result[$field] = null;
            }

            $file = $request->file($field);
            if ($file === null || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $label = ucfirst($field);
            if ((int) $file['error'] === UPLOAD_ERR_INI_SIZE || (int) $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                return [$result, $label . ' file exceeds the server upload limit (upload_max_filesize / post_max_size).'];
            }
            if ((int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                return [$result, $label . ' upload failed.'];
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $rule['exts'], true)) {
                return [$result, $label . ' must be one of: ' . implode(', ', $rule['exts']) . '.'];
            }
            if ((int) $file['size'] > self::MAX_UPLOAD_BYTES) {
                return [$result, $label . ' file must be 100 MB or smaller.'];
            }

            $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $mimeOk = str_starts_with($mime, $rule['prefix']) || $mime === 'application/ogg'
                || ($field === 'video' && $mime === 'application/octet-stream');
            if (!$mimeOk) {
                return [$result, $label . ' file content does not match its type.'];
            }

            $directory = dirname(__DIR__, 3) . '/public/uploads/library';
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                return [$result, 'Unable to create upload directory.'];
            }

            $name = $field . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
                return [$result, $label . ' upload failed.'];
            }

            $previous = $result[$field];
            $result[$field] = 'uploads/library/' . $name;
            if ($previous !== null && $previous !== ($existing[$field] ?? null)) {
                $this->deleteFiles([$previous]);
            }
        }

        return [$result, null];
    }

    private function fileValues(array $item): array
    {
        return ['audio' => $item['audio'] ?? null, 'video' => $item['video'] ?? null, 'image' => $item['image'] ?? null];
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && preg_match('#^uploads/library/[A-Za-z0-9._-]+$#', $path) === 1) {
                $full = dirname(__DIR__, 3) . '/public/' . $path;
                if (is_file($full)) {
                    @unlink($full);
                }
            }
        }
    }

    private function isHttpUrl(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function renderIndex(Csrf $csrf, Library $library, array $options = []): Response
    {
        $search = (string) ($options['search'] ?? '');
        $status = (string) ($options['status'] ?? 'all');

        return $this->view('library.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'libraryUrl' => $this->libraryUrl(),
            'csrfField' => $csrf->field(),
            'items' => $library->search($search, $status),
            'totalItems' => $library->countAll(),
            'mediaFields' => Library::MEDIA_FIELDS,
            'editingItem' => $options['editingItem'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => isset($options['old']) ? $this->flattenOld($options['old']) : [],
            'search' => $search,
            'status' => $status,
        ]);
    }

    private function flattenOld(array $input): array
    {
        return ['slug' => $input['slug'], 'status' => $input['status']] + array_map(
            static fn (?string $value): string => $value ?? '',
            $input['media']
        );
    }

    private function libraryUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/library';
    }

    private function itemId(string $id): int
    {
        $itemId = filter_var($id, FILTER_VALIDATE_INT);
        if ($itemId === false || $itemId < 1) {
            throw new HttpException(404, 'Library item not found.');
        }

        return $itemId;
    }
}
