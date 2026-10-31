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
use App\Models\Riyaz;

class RiyazController extends Controller
{
    private const MAX_JSON_BYTES = 10485760;

    public function index(Request $request, Csrf $csrf, Riyaz $riyaz, Library $library): Response
    {
        $editing = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editing = $riyaz->find($editId);
            if ($editing === null) {
                throw new HttpException(404, 'Riyaz record not found.');
            }
        }

        return $this->renderIndex($csrf, $riyaz, $library, [
            'editingRiyaz' => $editing,
            'notice' => match ((string) $request->query('notice', '')) {
                'created' => 'Riyaz record created.',
                'saved' => 'Riyaz record updated.',
                'deleted' => 'Riyaz record deleted.',
                default => null,
            },
        ]);
    }

    public function store(
        Request $request,
        Csrf $csrf,
        Riyaz $riyaz,
        Library $library,
        Session $session
    ): Response {
        $mediaId = $this->mediaId($request->input('media_id'));
        $error = $this->validateMediaId($mediaId, $library);
        if ($error !== null) {
            return $this->renderIndex($csrf, $riyaz, $library, ['error' => $error, 'old' => ['media_id' => $request->input('media_id')]]);
        }

        [$path, $uploadError] = $this->uploadJson($request);
        if ($uploadError !== null) {
            return $this->renderIndex($csrf, $riyaz, $library, ['error' => $uploadError, 'old' => ['media_id' => $mediaId]]);
        }

        try {
            $riyaz->create($mediaId, $path, (int) $session->get('admin_id'));
        } catch (\Throwable $error) {
            $this->deleteJson($path);
            throw $error;
        }

        return $this->redirect($this->riyazUrl() . '?notice=created');
    }

    public function update(
        string $id,
        Request $request,
        Csrf $csrf,
        Riyaz $riyaz,
        Library $library,
        Session $session
    ): Response {
        $riyazId = $this->recordId($id);
        $record = $riyaz->find($riyazId);
        if ($record === null) {
            throw new HttpException(404, 'Riyaz record not found.');
        }

        $mediaId = $this->mediaId($request->input('media_id'));
        $error = $this->validateMediaId($mediaId, $library);
        if ($error !== null) {
            return $this->renderIndex($csrf, $riyaz, $library, [
                'editingRiyaz' => $record,
                'error' => $error,
                'old' => ['media_id' => $request->input('media_id')],
            ]);
        }

        [$uploadedPath, $uploadError] = $this->uploadJson($request, true);
        if ($uploadError !== null) {
            return $this->renderIndex($csrf, $riyaz, $library, [
                'editingRiyaz' => $record,
                'error' => $uploadError,
                'old' => ['media_id' => $mediaId],
            ]);
        }
        $path = $uploadedPath ?? (string) $record['synced_json'];
        try {
            $riyaz->update($riyazId, $mediaId, $path, (int) $session->get('admin_id'));
        } catch (\Throwable $error) {
            if ($uploadedPath !== null) {
                $this->deleteJson($uploadedPath);
            }
            throw $error;
        }
        if ($uploadedPath !== null) {
            $this->deleteJson($record['synced_json']);
        }

        return $this->redirect($this->riyazUrl() . '?notice=saved');
    }

    public function destroy(string $id, Riyaz $riyaz): Response
    {
        $riyazId = $this->recordId($id);
        $record = $riyaz->find($riyazId);
        if ($record === null) {
            throw new HttpException(404, 'Riyaz record not found.');
        }
        $riyaz->delete($riyazId);
        $this->deleteJson($record['synced_json']);

        return $this->redirect($this->riyazUrl() . '?notice=deleted');
    }

    private function mediaId(mixed $value): int
    {
        $mediaId = filter_var($value, FILTER_VALIDATE_INT);

        return $mediaId !== false && $mediaId > 0 ? $mediaId : 0;
    }

    private function validateMediaId(int $mediaId, Library $library): ?string
    {
        if ($mediaId < 1 || $library->find($mediaId) === null) {
            return 'Select a valid library media item.';
        }

        return null;
    }

    /** @return array{0:?string,1:?string} */
    private function uploadJson(Request $request, bool $optional = false): array
    {
        $file = $request->file('synced_json');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($request->input('remove_synced_json') === '1') {
                return [null, $optional ? null : 'A JSON file is required.'];
            }

            return $optional ? [null, null] : [null, 'A JSON file is required.'];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            return [null, 'JSON file exceeds the server upload limit.'];
        }
        if (
            $errorCode !== UPLOAD_ERR_OK
            || !is_string($file['tmp_name'] ?? null)
            || !is_uploaded_file($file['tmp_name'])
        ) {
            return [null, 'JSON file upload failed.'];
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_JSON_BYTES) {
            return [null, 'JSON file must be 10 MB or smaller.'];
        }
        if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'json') {
            return [null, 'Upload a file with the .json extension.'];
        }
        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false) {
            return [null, 'Unable to read the uploaded JSON file.'];
        }
        json_decode($contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [null, 'The uploaded file must contain valid JSON.'];
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/riyaz';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return [null, 'Unable to create Riyaz upload directory.'];
        }
        $name = 'synced-' . bin2hex(random_bytes(12)) . '.json';
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            return [null, 'JSON file upload failed.'];
        }

        return ['uploads/riyaz/' . $name, null];
    }

    private function deleteJson(mixed $path): void
    {
        if (!is_string($path) || preg_match('#^uploads/riyaz/[A-Za-z0-9._-]+\.json$#', $path) !== 1) {
            return;
        }
        $fullPath = dirname(__DIR__, 3) . '/public/' . $path;
        if (is_file($fullPath) && !unlink($fullPath)) {
            error_log("Unable to remove Riyaz JSON file: {$fullPath}");
        }
    }

    private function renderIndex(Csrf $csrf, Riyaz $riyaz, Library $library, array $options = []): Response
    {
        return $this->view('riyaz.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'riyazUrl' => $this->riyazUrl(),
            'csrfField' => $csrf->field(),
            'items' => $riyaz->all(),
            'mediaItems' => $riyaz->mediaOptions(),
            'editingRiyaz' => $options['editingRiyaz'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
        ]);
    }

    private function riyazUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/riyaz';
    }

    private function recordId(string $id): int
    {
        $recordId = filter_var($id, FILTER_VALIDATE_INT);
        if ($recordId === false || $recordId < 1) {
            throw new HttpException(404, 'Riyaz record not found.');
        }

        return $recordId;
    }
}
