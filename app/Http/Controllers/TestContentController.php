<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ContentCategory;
use App\Models\TestContent;

class TestContentController extends Controller
{
    private const MAX_THUMBNAIL_BYTES = 104857600;

    public function index(
        Request $request,
        Csrf $csrf,
        TestContent $contents,
        ContentCategory $categories
    ): Response {
        $editing = null;
        $categorySlugs = [];
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editing = $contents->find($editId);
            if ($editing === null) {
                throw new HttpException(404, 'Text content not found.');
            }
            $categorySlugs = $contents->categorySlugs((string) $editing['slug']);
        }

        return $this->renderIndex($request, $csrf, $contents, $categories, [
            'editingContent' => $editing,
            'categorySlugs' => $categorySlugs,
            'notice' => match ((string) $request->query('notice', '')) {
                'created' => 'Text content created.',
                'saved' => 'Text content updated.',
                'deleted' => 'Text content deleted.',
                default => null,
            },
        ]);
    }

    public function store(
        Request $request,
        Csrf $csrf,
        TestContent $contents,
        ContentCategory $categories,
        Session $session
    ): Response {
        $input = $this->input($request);
        $error = $this->validateInput($input, $contents, $categories);
        if ($error !== null) {
            return $this->renderIndex($request, $csrf, $contents, $categories, [
                'error' => $error,
                'old' => $input,
            ]);
        }

        [$thumbnail, $uploadError] = $this->handleThumbnail($request, null);
        if ($uploadError !== null) {
            return $this->renderIndex($request, $csrf, $contents, $categories, [
                'error' => $uploadError,
                'old' => $input,
            ]);
        }

        $categorySlugs = $input['category_slugs'];
        $fields = $input;
        unset($fields['category_slugs']);
        $fields['thumb_nail'] = $thumbnail;
        $fields['updated_by'] = (int) $session->get('admin_id');
        try {
            $contents->create($fields, $categorySlugs);
        } catch (\Throwable $error) {
            $this->deleteThumbnail($thumbnail);
            throw $error;
        }

        return $this->redirect($this->contentUrl() . '?notice=created');
    }

    public function update(
        string $id,
        Request $request,
        Csrf $csrf,
        TestContent $contents,
        ContentCategory $categories,
        Session $session
    ): Response {
        $contentId = $this->contentId($id);
        $content = $contents->find($contentId);
        if ($content === null) {
            throw new HttpException(404, 'Text content not found.');
        }

        $input = $this->input($request);
        $error = $this->validateInput($input, $contents, $categories, $contentId);
        if ($error !== null) {
            return $this->renderIndex($request, $csrf, $contents, $categories, [
                'editingContent' => $content,
                'categorySlugs' => $contents->categorySlugs((string) $content['slug']),
                'error' => $error,
                'old' => $input,
            ]);
        }

        $existingThumbnail = $content['thumb_nail'];
        [$thumbnail, $uploadError] = $this->handleThumbnail($request, $existingThumbnail);
        if ($uploadError !== null) {
            return $this->renderIndex($request, $csrf, $contents, $categories, [
                'editingContent' => $content,
                'categorySlugs' => $input['category_slugs'],
                'error' => $uploadError,
                'old' => $input,
            ]);
        }

        $categorySlugs = $input['category_slugs'];
        $fields = $input;
        unset($fields['category_slugs']);
        $fields['thumb_nail'] = $thumbnail;
        $fields['updated_by'] = (int) $session->get('admin_id');
        try {
            $contents->update($contentId, $fields, $categorySlugs);
        } catch (\Throwable $error) {
            if ($thumbnail !== $existingThumbnail) {
                $this->deleteThumbnail($thumbnail);
            }
            throw $error;
        }
        if ($existingThumbnail !== $thumbnail) {
            $this->deleteThumbnail($existingThumbnail);
        }

        return $this->redirect($this->contentUrl() . '?notice=saved');
    }

    public function destroy(string $id, TestContent $contents): Response
    {
        $contentId = $this->contentId($id);
        $content = $contents->find($contentId);
        if ($content === null) {
            throw new HttpException(404, 'Text content not found.');
        }
        $contents->delete($contentId);
        $this->deleteThumbnail($content['thumb_nail']);

        return $this->redirect($this->contentUrl() . '?notice=deleted');
    }

    private function input(Request $request): array
    {
        $text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';
        $shortTest = $request->input('short_test');
        $categories = $request->input('category_slugs', []);
        if (
            !is_array($categories)
            || count($categories) !== count(array_filter($categories, static fn (mixed $slug): bool => is_string($slug)))
        ) {
            $categories = null;
        } else {
            $categories = array_values($categories);
        }

        return [
            'slug' => strtolower($text($request->input('slug'))),
            'title' => $text($request->input('title')),
            'short_test' => is_string($shortTest) && trim($shortTest) !== '' ? trim($shortTest) : null,
            'full_text' => is_string($request->input('full_text')) ? trim($request->input('full_text')) : '',
            'category_slugs' => $categories,
        ];
    }

    private function validateInput(
        array $input,
        TestContent $contents,
        ContentCategory $categories,
        ?int $excludeId = null
    ): ?string {
        if ($input['title'] === '' || mb_strlen($input['title']) > 255) {
            return 'Title is required and must be 255 characters or fewer.';
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input['slug']) !== 1 || strlen($input['slug']) > 160) {
            return 'Slug may contain only lowercase letters, numbers, and hyphens (max 160 characters).';
        }
        if ($contents->slugExists($input['slug'], $excludeId)) {
            return 'Text content with this slug already exists.';
        }
        if ($input['short_test'] !== null && mb_strlen($input['short_test']) > 1000) {
            return 'Short text must be 1,000 characters or fewer.';
        }
        if ($input['full_text'] === '') {
            return 'Full text is required.';
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

        return null;
    }

    /** @return array{0:?string,1:?string} */
    private function handleThumbnail(Request $request, ?string $existing): array
    {
        $file = $request->file('thumb_nail');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($request->input('remove_thumb_nail') === '1') {
                return [null, null];
            }

            return [$existing, null];
        }
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            return [$existing, 'Thumbnail exceeds the server upload limit.'];
        }
        if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return [$existing, 'Thumbnail upload failed.'];
        }
        if ((int) $file['size'] > self::MAX_THUMBNAIL_BYTES) {
            return [$existing, 'Thumbnail must be 100 MB or smaller.'];
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $validExtension = in_array($extension, ['webp', 'jpg', 'jpeg'], true);
        $validMime = ($extension === 'webp' && $mime === 'image/webp')
            || (in_array($extension, ['jpg', 'jpeg'], true) && $mime === 'image/jpeg');
        if (!$validExtension || !$validMime) {
            return [$existing, 'Thumbnail must be a WebP or JPEG image.'];
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/test-content';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return [$existing, 'Unable to create thumbnail upload directory.'];
        }
        $name = 'thumbnail-' . bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            return [$existing, 'Thumbnail upload failed.'];
        }

        return ['uploads/test-content/' . $name, null];
    }

    private function deleteThumbnail(mixed $path): void
    {
        if (!is_string($path) || preg_match('#^uploads/test-content/[A-Za-z0-9._-]+$#', $path) !== 1) {
            return;
        }
        $fullPath = dirname(__DIR__, 3) . '/public/' . $path;
        if (is_file($fullPath) && !unlink($fullPath)) {
            error_log("Unable to remove text-content thumbnail: {$fullPath}");
        }
    }

    private function renderIndex(
        Request $request,
        Csrf $csrf,
        TestContent $contents,
        ContentCategory $categories,
        array $options = []
    ): Response {
        $search = trim((string) $request->query('search', ''));

        return $this->view('test_content.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'contentUrl' => $this->contentUrl(),
            'csrfField' => $csrf->field(),
            'items' => $contents->search($search),
            'categories' => $categories->all(),
            'editingContent' => $options['editingContent'] ?? null,
            'selectedCategorySlugs' => $options['categorySlugs'] ?? ($options['old']['category_slugs'] ?? []),
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
            'search' => $search,
        ]);
    }

    private function contentUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/test-content';
    }

    private function contentId(string $id): int
    {
        $contentId = filter_var($id, FILTER_VALIDATE_INT);
        if ($contentId === false || $contentId < 1) {
            throw new HttpException(404, 'Text content not found.');
        }

        return $contentId;
    }
}
