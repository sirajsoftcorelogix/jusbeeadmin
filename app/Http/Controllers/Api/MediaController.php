<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Library;
use App\Models\MediaCategory;

class MediaController extends Controller
{
    public function category(string $slug, MediaCategory $categories): Response
    {
        $category = $categories->withChildrenBySlug($slug);
        if ($category === null) {
            return $this->fail('Media category not found.', 404);
        }

        $category['category_id'] = (int) $category['category_id'];
        $category['parent_id'] = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;
        $category['children'] = array_map(
            static function (array $child): array {
                $child['category_id'] = (int) $child['category_id'];
                $child['parent_id'] = $child['parent_id'] !== null ? (int) $child['parent_id'] : null;

                return $child;
            },
            $category['children']
        );

        return $this->json(['success' => true, 'category' => $category]);
    }

    public function categoryMedia(string $slug, MediaCategory $categories, Library $library): Response
    {
        $category = $categories->findBySlug($slug);
        if ($category === null) {
            return $this->fail('Media category not found.', 404);
        }

        $media = array_map(
            fn (array $item): array => $this->presentMedia($item),
            $library->activeForCategory($slug)
        );

        return $this->json([
            'success' => true,
            'category' => [
                'category_id' => (int) $category['category_id'],
                'category_name' => $category['category_name'],
                'slug' => $category['slug'],
            ],
            'media' => $media,
        ]);
    }

    public function content(string $slug, Library $library): Response
    {
        $media = $library->findActiveBySlug($slug);
        if ($media === null) {
            return $this->fail('Media content not found.', 404);
        }

        return $this->json(['success' => true, 'media' => $this->presentMedia($media)]);
    }

    private function presentMedia(array $media): array
    {
        $media['id'] = (int) $media['id'];
        if (isset($media['categories'])) {
            $media['categories'] = array_map(
                static function (array $category): array {
                    $category['category_id'] = (int) $category['category_id'];
                    $category['parent_id'] = $category['parent_id'] !== null
                        ? (int) $category['parent_id']
                        : null;

                    return $category;
                },
                $media['categories']
            );
        }

        return $media;
    }

    private function fail(string $message, int $status): Response
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }
}
