<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\ContentPage;

class ContentController extends Controller
{
    public function index(Request $request, Csrf $csrf, ContentPage $pages): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        if (!in_array($status, ['all', 'draft', 'published'], true)) {
            $status = 'all';
        }

        $editingPage = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editingPage = $pages->find($editId);
            if ($editingPage === null) {
                throw new HttpException(404, 'Content page not found.');
            }
        }

        $notice = match ((string) $request->query('notice', '')) {
            'created' => 'Content page created.',
            'saved' => 'Content page updated.',
            'published' => 'Content page published.',
            'draft' => 'Content page moved to drafts.',
            'deleted' => 'Content page deleted.',
            default => null,
        };

        return $this->renderIndex($csrf, $pages, [
            'editingPage' => $editingPage,
            'search' => $search,
            'status' => $status,
            'notice' => $notice,
        ]);
    }

    public function store(Request $request, Csrf $csrf, ContentPage $pages): Response
    {
        $data = $this->validate($request, [
            'title' => 'required|min:2|max:160',
            'body' => 'required|max:20000',
            'status' => 'required',
        ]);

        if (!$this->hasTextFields($data, ['title', 'body', 'status'])) {
            return $this->renderIndex($csrf, $pages, [
                'error' => 'Enter a valid title, page content, and status.',
                'old' => $data,
            ]);
        }

        $title = trim($data['title']);
        $body = trim($data['body']);
        $status = $data['status'];
        if (mb_strlen($title) < 2 || mb_strlen($title) > 160 || $body === '' || mb_strlen($body) > 20000) {
            return $this->renderIndex($csrf, $pages, [
                'error' => 'The title must be 2–160 characters and page content must not be empty.',
                'old' => $data,
            ]);
        }
        if (!in_array($status, ['draft', 'published'], true)) {
            return $this->renderIndex($csrf, $pages, [
                'error' => 'Select a valid publishing status.',
                'old' => $data,
            ]);
        }

        $slug = $this->uniqueSlug($title, $pages);
        $pages->create($title, $slug, $body, $status);

        return $this->redirect($this->contentUrl() . '?notice=created');
    }

    public function update(string $id, Request $request, Csrf $csrf, ContentPage $pages): Response
    {
        $pageId = $this->pageId($id);
        $page = $pages->find($pageId);
        if ($page === null) {
            throw new HttpException(404, 'Content page not found.');
        }

        $data = $this->validate($request, [
            'title' => 'required|min:2|max:160',
            'body' => 'required|max:20000',
            'status' => 'required',
        ]);

        if (!$this->hasTextFields($data, ['title', 'body', 'status'])) {
            return $this->renderIndex($csrf, $pages, [
                'editingPage' => $page,
                'error' => 'Enter a valid title, page content, and status.',
                'old' => $data,
            ]);
        }

        $title = trim($data['title']);
        $body = trim($data['body']);
        $status = $data['status'];
        if (mb_strlen($title) < 2 || mb_strlen($title) > 160 || $body === '' || mb_strlen($body) > 20000) {
            return $this->renderIndex($csrf, $pages, [
                'editingPage' => $page,
                'error' => 'The title must be 2–160 characters and page content must not be empty.',
                'old' => $data,
            ]);
        }
        if (!in_array($status, ['draft', 'published'], true)) {
            return $this->renderIndex($csrf, $pages, [
                'editingPage' => $page,
                'error' => 'Select a valid publishing status.',
                'old' => $data,
            ]);
        }

        $slug = $this->uniqueSlug($title, $pages, $pageId);
        $pages->update($pageId, $title, $slug, $body, $status);

        return $this->redirect($this->contentUrl() . '?notice=saved');
    }

    public function setStatus(string $id, Request $request, ContentPage $pages): Response
    {
        $pageId = $this->pageId($id);
        if ($pages->find($pageId) === null) {
            throw new HttpException(404, 'Content page not found.');
        }

        $status = $request->input('status');
        if (!is_string($status) || !in_array($status, ['draft', 'published'], true)) {
            throw new HttpException(422, 'Select a valid publishing status.');
        }

        $pages->setStatus($pageId, $status);

        return $this->redirect($this->contentUrl() . '?notice=' . $status);
    }

    public function destroy(string $id, ContentPage $pages): Response
    {
        $pageId = $this->pageId($id);
        if ($pages->find($pageId) === null) {
            throw new HttpException(404, 'Content page not found.');
        }

        $pages->delete($pageId);

        return $this->redirect($this->contentUrl() . '?notice=deleted');
    }

    public function show(string $slug, ContentPage $pages): Response
    {
        $page = $pages->findBySlug($slug);
        if ($page === null) {
            throw new HttpException(404, 'Page not found.');
        }

        return $this->view('content/show', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'page' => $page,
        ]);
    }

    private function renderIndex(Csrf $csrf, ContentPage $pages, array $options = []): Response
    {
        $search = (string) ($options['search'] ?? '');
        $status = (string) ($options['status'] ?? 'all');

        return $this->view('content.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'contentUrl' => $this->contentUrl(),
            'csrfField' => $csrf->field(),
            'pages' => $pages->search($search, $status),
            'totalPages' => $pages->countAll(),
            'editingPage' => $options['editingPage'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
            'search' => $search,
            'status' => $status,
        ]);
    }

    private function hasTextFields(array $data, array $fields): bool
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                return false;
            }
        }

        return true;
    }

    private function uniqueSlug(string $title, ContentPage $pages, ?int $excludeId = null): string
    {
        $asciiTitle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
        $slug = strtolower((string) $asciiTitle);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = substr($slug !== '' ? $slug : 'page', 0, 160);
        $candidate = $slug;
        $suffix = 2;

        while ($pages->slugExists($candidate, $excludeId)) {
            $ending = '-' . $suffix++;
            $candidate = substr($slug, 0, 160 - strlen($ending)) . $ending;
        }

        return $candidate;
    }

    private function contentUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/content';
    }

    private function pageId(string $id): int
    {
        $pageId = filter_var($id, FILTER_VALIDATE_INT);
        if ($pageId === false || $pageId < 1) {
            throw new HttpException(404, 'Content page not found.');
        }

        return $pageId;
    }
}
