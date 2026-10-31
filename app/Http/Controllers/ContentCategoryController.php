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

class ContentCategoryController extends Controller
{
    public function index(Request $request, Csrf $csrf, ContentCategory $categories): Response
    {
        $editing = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false && $editId > 0) {
            $editing = $categories->find($editId);
            if ($editing === null) {
                throw new HttpException(404, 'Content category not found.');
            }
        }

        return $this->renderIndex($csrf, $categories, [
            'editingCategory' => $editing,
            'notice' => match ((string) $request->query('notice', '')) {
                'created' => 'Category created.',
                'saved' => 'Category updated.',
                'deleted' => 'Category deleted.',
                default => null,
            },
        ]);
    }

    public function store(Request $request, Csrf $csrf, ContentCategory $categories, Session $session): Response
    {
        $input = $this->input($request);
        $error = $this->validateInput($input, $categories);
        if ($error !== null) {
            return $this->renderIndex($csrf, $categories, ['error' => $error, 'old' => $input]);
        }

        $input['updated_by'] = (int) $session->get('admin_id');
        $categories->create($input);

        return $this->redirect($this->categoriesUrl() . '?notice=created');
    }

    public function update(
        string $id,
        Request $request,
        Csrf $csrf,
        ContentCategory $categories,
        Session $session
    ): Response {
        $categoryId = $this->categoryId($id);
        $category = $categories->find($categoryId);
        if ($category === null) {
            throw new HttpException(404, 'Content category not found.');
        }

        $input = $this->input($request);
        $error = $this->validateInput($input, $categories, $categoryId);
        if ($error !== null) {
            return $this->renderIndex($csrf, $categories, [
                'editingCategory' => $category,
                'error' => $error,
                'old' => $input,
            ]);
        }

        $input['updated_by'] = (int) $session->get('admin_id');
        $categories->update($categoryId, $input);

        return $this->redirect($this->categoriesUrl() . '?notice=saved');
    }

    public function destroy(string $id, ContentCategory $categories): Response
    {
        $categoryId = $this->categoryId($id);
        if ($categories->find($categoryId) === null) {
            throw new HttpException(404, 'Content category not found.');
        }
        $categories->delete($categoryId);

        return $this->redirect($this->categoriesUrl() . '?notice=deleted');
    }

    private function input(Request $request): array
    {
        $text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';
        $parent = $request->input('parent_id');
        if ($parent === null || $parent === '') {
            $parent = null;
        } else {
            $parentId = filter_var($parent, FILTER_VALIDATE_INT);
            $parent = $parentId !== false && $parentId > 0 ? $parentId : -1;
        }

        return [
            'category_name' => $text($request->input('category_name')),
            'slug' => strtolower($text($request->input('slug'))),
            'parent_id' => $parent,
        ];
    }

    private function validateInput(array $input, ContentCategory $categories, ?int $excludeId = null): ?string
    {
        if ($input['category_name'] === '' || mb_strlen($input['category_name']) > 255) {
            return 'Category name is required and must be 255 characters or fewer.';
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input['slug']) !== 1 || strlen($input['slug']) > 160) {
            return 'Slug may contain only lowercase letters, numbers, and hyphens (max 160 characters).';
        }
        if ($categories->slugExists($input['slug'], $excludeId)) {
            return 'A category with this slug already exists.';
        }
        if ($input['parent_id'] === -1) {
            return 'Select a valid parent category.';
        }
        if ($input['parent_id'] !== null) {
            if ($excludeId !== null && $categories->parentWouldCreateCycle($excludeId, $input['parent_id'])) {
                return 'A category cannot be its own parent or descendant.';
            }
            if ($categories->find($input['parent_id']) === null) {
                return 'Select a valid parent category.';
            }
        }

        return null;
    }

    private function renderIndex(Csrf $csrf, ContentCategory $categories, array $options = []): Response
    {
        return $this->view('content_categories.index', [
            'appName' => (string) config('app.name'),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
            'categoriesUrl' => $this->categoriesUrl(),
            'csrfField' => $csrf->field(),
            'categories' => $categories->all(),
            'editingCategory' => $options['editingCategory'] ?? null,
            'error' => $options['error'] ?? null,
            'notice' => $options['notice'] ?? null,
            'old' => $options['old'] ?? [],
        ]);
    }

    private function categoriesUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/content-categories';
    }

    private function categoryId(string $id): int
    {
        $categoryId = filter_var($id, FILTER_VALIDATE_INT);
        if ($categoryId === false || $categoryId < 1) {
            throw new HttpException(404, 'Content category not found.');
        }

        return $categoryId;
    }
}
