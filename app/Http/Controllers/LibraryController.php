<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Library;
use App\Models\MediaCategory;

class LibraryController extends Controller
{
    private const MAX_UPLOAD_BYTES = 104857600;

    public function index(Request $request, Csrf $csrf, Library $library, MediaCategory $categories): Response
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

        $mappingItem = null;
        $mappingId = filter_var($request->query('category-mapping'), FILTER_VALIDATE_INT);
        if ($mappingId !== false && $mappingId > 0) {
            $mappingItem = $library->find($mappingId);
            if ($mappingItem === null) {
                throw new HttpException(404, 'Library item not found.');
            }
        }

        $notice = match ((string) $request->query('notice', '')) {
            'created' => 'Library item created.',
            'saved' => 'Library item updated.',
            'deleted' => 'Library item deleted.',
            'categories-saved' => 'Category mapping updated.',
            default => null,
        };

        return $this->renderIndex($csrf, $library, $categories, [
            'editingItem' => $editingItem,
            'categorySlugs' => $editingItem !== null ? $library->categorySlugs((string) $editingItem['slug']) : [],
            'mappingItem' => $mappingItem,
            'mappingCategorySlugs' => $mappingItem !== null
                ? $library->categorySlugs((string) $mappingItem['slug'])
                : [],
            'search' => $search,
            'status' => $status,
            'notice' => $notice,
        ]);
    }

    public function updateCategoryMapping(
        string $id,
        Request $request,
        Csrf $csrf,
        Library $library,
        MediaCategory $categories,
        Session $session
    ): Response {
        $itemId = $this->itemId($id);
        $item = $library->find($itemId);
        if ($item === null) {
            throw new HttpException(404, 'Library item not found.');
        }

        $categorySlugs = $request->input('category_slugs', []);
        if (
            !is_array($categorySlugs)
            || count($categorySlugs) !== count(array_filter(
                $categorySlugs,
                static fn (mixed $slug): bool => is_string($slug) && $slug !== ''
            ))
            || !$categories->slugsExist($categorySlugs)
        ) {
            return $this->renderIndex($csrf, $library, $categories, [
                'mappingItem' => $item,
                'mappingCategorySlugs' => is_array($categorySlugs) ? $categorySlugs : [],
                'mappingError' => 'Select only valid categories.',
            ]);
        }

        $library->updateCategoryMappings(
            (string) $item['slug'],
            array_values(array_unique($categorySlugs)),
            (int) $session->get('admin_id')
        );

        return $this->redirect($this->libraryUrl() . '?notice=categories-saved');
    }

    public function store(
        Request $request,
        Csrf $csrf,
        Library $library,
        MediaCategory $categories,
        Session $session
    ): Response {
        $input = $this->input($request);
        $error = $this->validateInput($input, $library, $categories);
        if ($error !== null) {
            return $this->renderIndex($csrf, $library, $categories, ['error' => $error, 'old' => $input]);
        }

        [$files, $uploadError] = $this->handleUploads($request, []);
        $error = $uploadError ?? $this->validateMediaFile($input['media_type'], $files['media_file']);
        if ($error !== null) {
            $this->deleteFiles($files);

            return $this->renderIndex($csrf, $library, $categories, ['error' => $error, 'old' => $input]);
        }

        $categorySlugs = $input['category_slugs'];
        $fields = $input + $files;
        unset($fields['category_slugs']);
        $fields['updated_by'] = (int) $session->get('admin_id');
        try {
            $library->create($fields, $categorySlugs);
        } catch (\Throwable $error) {
            $this->deleteFiles($files);
            throw $error;
        }

        return $this->redirect($this->libraryUrl() . '?notice=created');
    }

    public function update(
        string $id,
        Request $request,
        Csrf $csrf,
        Library $library,
        MediaCategory $categories,
        Session $session
    ): Response {
        $itemId = $this->itemId($id);
        $item = $library->find($itemId);
        if ($item === null) {
            throw new HttpException(404, 'Library item not found.');
        }

        $input = $this->input($request);
        $error = $this->validateInput($input, $library, $categories, $itemId);
        if ($error !== null) {
            return $this->renderIndex($csrf, $library, $categories, [
                'editingItem' => $item,
                'categorySlugs' => $library->categorySlugs((string) $item['slug']),
                'error' => $error,
                'old' => $input,
            ]);
        }

        $existing = $this->fileValues($item);
        [$files, $uploadError] = $this->handleUploads($request, $existing);
        $error = $uploadError ?? $this->validateMediaFile($input['media_type'], $files['media_file']);
        if ($error !== null) {
            $this->deleteFiles(array_diff($files, $existing));

            return $this->renderIndex($csrf, $library, $categories, [
                'editingItem' => $item,
                'categorySlugs' => $input['category_slugs'],
                'error' => $error,
                'old' => $input,
            ]);
        }

        $categorySlugs = $input['category_slugs'];
        $fields = $input + $files;
        unset($fields['category_slugs']);
        $fields['updated_by'] = (int) $session->get('admin_id');
        try {
            $library->update($itemId, $fields, $categorySlugs);
        } catch (\Throwable $error) {
            $this->deleteFiles(array_diff($files, $existing));
            throw $error;
        }
        $this->deleteFiles(array_diff($existing, $files));

        return $this->redirect($this->libraryUrl() . '?notice=saved');
    }

    public function destroy(string $id, Library $library): Response
    {
        $itemId = $this->itemId($id);
        $item = $library->find($itemId);
        if ($item === null) {
            throw new HttpException(404, 'Library item not found.');
        }

        $library->delete($itemId);
        $this->deleteFiles($this->fileValues($item));

        return $this->redirect($this->libraryUrl() . '?notice=deleted');
    }

    /**
     * @return array{title:string,slug:string,media_type:string,play_time:?string,artist:?string,description:?string,status:string}
     */
    private function input(Request $request): array
    {
        $text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';
        $optionalText = static function (mixed $value) use ($text): ?string {
            $value = $text($value);

            return $value !== '' ? $value : null;
        };

        $categorySlugs = $request->input('category_slugs', []);
        if (
            !is_array($categorySlugs)
            || count($categorySlugs) !== count(array_filter(
                $categorySlugs,
                static fn (mixed $slug): bool => is_string($slug)
            ))
        ) {
            $categorySlugs = null;
        } else {
            $categorySlugs = array_values($categorySlugs);
        }

        return [
            'title' => $text($request->input('title')),
            'slug' => strtolower($text($request->input('slug'))),
            'media_type' => $text($request->input('media_type')),
            'play_time' => $optionalText($request->input('play_time')),
            'artist' => $optionalText($request->input('artist')),
            'description' => $optionalText($request->input('description')),
            'category_slugs' => $categorySlugs,
            'status' => $text($request->input('status')),
        ];
    }

    private function validateInput(
        array $input,
        Library $library,
        MediaCategory $categories,
        ?int $excludeId = null
    ): ?string
    {
        if ($input['title'] === '' || mb_strlen($input['title']) > 255) {
            return 'Title is required and must be 255 characters or fewer.';
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input['slug']) !== 1 || strlen($input['slug']) > 160) {
            return 'Slug may contain only lowercase letters, numbers, and hyphens (max 160 characters).';
        }
        if ($library->slugExists($input['slug'], $excludeId)) {
            return 'An item with this slug already exists.';
        }
        if (!in_array($input['media_type'], Library::MEDIA_TYPES, true)) {
            return 'Select audio or video as the media type.';
        }
        if ($input['play_time'] !== null && mb_strlen($input['play_time']) > 20) {
            return 'Play time must be 20 characters or fewer.';
        }
        if ($input['artist'] !== null && mb_strlen($input['artist']) > 255) {
            return 'Artist must be 255 characters or fewer.';
        }
        if ($input['description'] !== null && strlen($input['description']) > 65535) {
            return 'Description must be 65,535 bytes or fewer.';
        }
        if (
            !is_array($input['category_slugs'])
            || count($input['category_slugs']) !== count(array_filter(
                $input['category_slugs'],
                static fn (mixed $slug): bool => is_string($slug) && $slug !== ''
            ))
            || !$categories->slugsExist($input['category_slugs'])
        ) {
            return 'Select only valid categories.';
        }
        if (!in_array($input['status'], ['active', 'inactive'], true)) {
            return 'Select a valid status.';
        }

        return null;
    }

    /**
     * @param array{thumbnail:?string,media_file:?string} $existing
     * @return array{0:array{thumbnail:?string,media_file:?string},1:?string}
     */
    private function handleUploads(Request $request, array $existing): array
    {
        $result = $existing + ['thumbnail' => null, 'media_file' => null];
        foreach (['thumbnail', 'media_file'] as $field) {
            if ($request->input('remove_' . $field) === '1') {
                $result[$field] = null;
            }

            $file = $request->file($field);
            if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $label = $field === 'media_file' ? 'Media file' : 'Thumbnail';
            $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                return [$result, "{$label} exceeds the server upload limit."];
            }
            if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                return [$result, "{$label} upload failed."];
            }
            if ((int) $file['size'] > self::MAX_UPLOAD_BYTES) {
                return [$result, "{$label} must be 100 MB or smaller."];
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if ($field === 'thumbnail') {
                $valid = in_array($extension, ['webp', 'jpg', 'jpeg'], true)
                    && in_array($mime, ['image/webp', 'image/jpeg'], true)
                    && (($extension === 'webp' && $mime === 'image/webp')
                        || (in_array($extension, ['jpg', 'jpeg'], true) && $mime === 'image/jpeg'));
            } else {
                $mediaMimes = [
                    'mp3' => ['audio/mpeg', 'audio/mp3'],
                    'mp4' => ['video/mp4'],
                    'wav' => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave'],
                    'mov' => ['video/quicktime'],
                    'm4v' => ['video/x-m4v', 'video/mp4'],
                    'm4a' => ['audio/mp4', 'audio/x-m4a'],
                ];
                $valid = isset($mediaMimes[$extension]) && in_array($mime, $mediaMimes[$extension], true);
                $expectedType = in_array($extension, ['mp3', 'wav', 'm4a'], true) ? 'audio' : 'video';
                if ($valid && $request->input('media_type') !== $expectedType) {
                    return [$result, "The selected media file does not match the {$expectedType} media type."];
                }
            }
            if (!$valid) {
                return [$result, $field === 'thumbnail'
                    ? 'Thumbnail must be a WebP or JPEG image.'
                    : 'Media file must be a supported MP3, MP4, WAV, MOV, M4V, or M4A file.'];
            }

            $directory = dirname(__DIR__, 3) . '/public/uploads/library';
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                return [$result, 'Unable to create upload directory.'];
            }

            $name = $field . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
                return [$result, "{$label} upload failed."];
            }

            $result[$field] = 'uploads/library/' . $name;
        }

        return [$result, null];
    }

    private function validateMediaFile(string $mediaType, ?string $path): ?string
    {
        if ($path === null || $path === '') {
            return 'Upload a media file.';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $expectedType = in_array($extension, ['mp3', 'wav', 'm4a'], true) ? 'audio' : 'video';
        if (!in_array($extension, ['mp3', 'mp4', 'wav', 'mov', 'm4v', 'm4a'], true) || $expectedType !== $mediaType) {
            return 'The media file extension must match the selected audio or video type.';
        }

        return null;
    }

    /** @return array{thumbnail:?string,media_file:?string} */
    private function fileValues(array $item): array
    {
        return [
            'thumbnail' => $item['thumbnail'] ?? null,
            'media_file' => $item['media_file'] ?? null,
        ];
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && preg_match('#^uploads/library/[A-Za-z0-9._-]+$#', $path) === 1) {
                $full = dirname(__DIR__, 3) . '/public/' . $path;
                if (is_file($full) && !unlink($full)) {
                    error_log("Unable to remove library upload: {$full}");
                }
            }
        }
    }

    private function renderIndex(
        Csrf $csrf,
        Library $library,
        MediaCategory $categories,
        array $options = []
    ): Response
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
            'categories' => $categories->all(),
            'editingItem' => $options['editingItem'] ?? null,
            'selectedCategorySlugs' => is_array($options['categorySlugs'] ?? ($options['old']['category_slugs'] ?? []))
                ? ($options['categorySlugs'] ?? ($options['old']['category_slugs'] ?? []))
                : [],
            'mappingItem' => $options['mappingItem'] ?? null,
            'selectedMappingCategorySlugs' => is_array($options['mappingCategorySlugs'] ?? [])
                ? $options['mappingCategorySlugs']
                : [],
            'mappingError' => $options['mappingError'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
            'search' => $search,
            'status' => $status,
        ]);
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
