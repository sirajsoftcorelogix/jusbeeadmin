<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\ContentCategory;
use App\Models\TextContent;

class TextContentController extends Controller
{
    public function byCategory(string $slug, ContentCategory $categories, TextContent $contents): Response
    {
        $category = $categories->findBySlug($slug);
        if ($category === null) {
            return $this->fail('Content category not found.', 404);
        }

        $items = array_map(
            static function (array $item): array {
                $item['id'] = (int) $item['id'];

                return $item;
            },
            $contents->byCategorySlug($slug)
        );

        return $this->json([
            'success' => true,
            'category' => [
                'category_id' => (int) $category['category_id'],
                'category_name' => $category['category_name'],
                'slug' => $category['slug'],
                'parent_id' => $category['parent_id'] !== null ? (int) $category['parent_id'] : null,
            ],
            'contents' => $items,
        ]);
    }

    public function bySlug(string $slug, TextContent $contents): Response
    {
        $item = $contents->findBySlug($slug);
        if ($item === null) {
            return $this->fail('Text content not found.', 404);
        }

        $item['id'] = (int) $item['id'];
        $item['categories'] = array_map(
            static function (array $category): array {
                $category['category_id'] = (int) $category['category_id'];
                $category['parent_id'] = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;

                return $category;
            },
            $contents->categoryNames($slug)
        );

        return $this->json(['success' => true, 'content' => $item]);
    }

    private function fail(string $message, int $status): Response
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }
}
