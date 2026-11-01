<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Library extends Model
{
    protected string $table = 'ju_library';

    public const MEDIA_TYPES = ['audio', 'video'];

    public function search(string $term = '', string $status = 'all'): array
    {
        $conditions = [];
        $parameters = [];

        if ($term !== '') {
            $conditions[] = '(title LIKE :title OR slug LIKE :slug OR artist LIKE :artist)';
            $parameters['title'] = $parameters['slug'] = $parameters['artist'] = '%' . $term . '%';
        }

        if ($status === 'active' || $status === 'inactive') {
            $conditions[] = 'ju_library.status = :status';
            $parameters['status'] = $status;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $statement = $this->db->connection()->prepare(
            "SELECT ju_library.*, ju_users.name AS updated_by_name,
                    (SELECT GROUP_CONCAT(category.category_name ORDER BY category.category_name SEPARATOR ', ')
                     FROM category_media_map category_map
                     JOIN media_categories category ON category.slug = category_map.category_slug
                     WHERE category_map.media_slug = ju_library.slug) AS category_names
             FROM {$this->table}
             LEFT JOIN ju_users ON ju_users.id = ju_library.updated_by{$where}
             ORDER BY ju_library.modified_at DESC, ju_library.id DESC"
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->connection()->query(
            "SELECT COUNT(*) FROM {$this->table}"
        )->fetchColumn();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug";
        $parameters = ['slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }

        $statement = $this->db->connection()->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $fields, array $categorySlugs): int
    {
        $connection = $this->db->connection();
        $connection->beginTransaction();
        try {
            $statement = $connection->prepare(
                "INSERT INTO {$this->table} (
                    title, slug, thumbnail, media_file, media_type, play_time,
                    artist, description, updated_by, status
                 ) VALUES (
                    :title, :slug, :thumbnail, :media_file, :media_type, :play_time,
                    :artist, :description, :updated_by, :status
                 )"
            );
            $statement->execute($fields);
            $id = (int) $connection->lastInsertId();
            $this->syncCategoryMappings($connection, $fields['slug'], $categorySlugs, (int) $fields['updated_by']);
            $connection->commit();

            return $id;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function update(int $id, array $fields, array $categorySlugs): void
    {
        $connection = $this->db->connection();
        $connection->beginTransaction();
        try {
            $statement = $connection->prepare(
                "UPDATE {$this->table}
                 SET title = :title, slug = :slug, thumbnail = :thumbnail,
                     media_file = :media_file, media_type = :media_type,
                     play_time = :play_time, artist = :artist, description = :description,
                     updated_by = :updated_by, status = :status
                 WHERE id = :id"
            );
            $statement->execute($fields + ['id' => $id]);
            $this->syncCategoryMappings($connection, $fields['slug'], $categorySlugs, (int) $fields['updated_by']);
            $connection->commit();
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->db->connection()->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );
        $statement->execute(['id' => $id]);
    }

    public function categorySlugs(string $mediaSlug): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT category_slug FROM category_media_map WHERE media_slug = :media_slug"
        );
        $statement->execute(['media_slug' => $mediaSlug]);

        return array_column($statement->fetchAll(), 'category_slug');
    }

    public function activeForCategory(string $categorySlug): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT media.id, media.title, media.slug, media.thumbnail, media.media_file,
                    media.media_type, media.play_time, media.artist, media.description,
                    media.created_at, media.modified_at
             FROM {$this->table} media
             INNER JOIN category_media_map category_map ON category_map.media_slug = media.slug
             WHERE category_map.category_slug = :category_slug AND media.status = 'active'
             ORDER BY media.title ASC, media.id DESC"
        );
        $statement->execute(['category_slug' => $categorySlug]);

        return $statement->fetchAll();
    }

    public function findActiveBySlug(string $slug): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT id, title, slug, thumbnail, media_file, media_type, play_time,
                    artist, description, created_at, modified_at
             FROM {$this->table}
             WHERE slug = :slug AND status = 'active'
             LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $media = $statement->fetch();
        if ($media === false) {
            return null;
        }

        $media['categories'] = $this->categoriesForMedia($slug);

        return $media;
    }

    private function categoriesForMedia(string $mediaSlug): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT category.category_id, category.category_name, category.slug, category.parent_id
             FROM category_media_map category_map
             INNER JOIN media_categories category ON category.slug = category_map.category_slug
             WHERE category_map.media_slug = :media_slug
             ORDER BY category.category_name ASC, category.category_id ASC"
        );
        $statement->execute(['media_slug' => $mediaSlug]);

        return $statement->fetchAll();
    }

    private function syncCategoryMappings(
        \PDO $connection,
        string $mediaSlug,
        array $categorySlugs,
        int $updatedBy
    ): void {
        $categorySlugs = array_values(array_unique($categorySlugs));
        if ($categorySlugs === []) {
            $connection->prepare(
                "DELETE FROM category_media_map WHERE media_slug = :media_slug"
            )->execute(['media_slug' => $mediaSlug]);

            return;
        }

        $placeholders = [];
        $deleteParameters = ['media_slug' => $mediaSlug];
        foreach ($categorySlugs as $index => $categorySlug) {
            $placeholder = 'category_' . $index;
            $placeholders[] = ':' . $placeholder;
            $deleteParameters[$placeholder] = $categorySlug;
        }
        $connection->prepare(
            "DELETE FROM category_media_map
             WHERE media_slug = :media_slug AND category_slug NOT IN (" . implode(', ', $placeholders) . ')'
        )->execute($deleteParameters);

        $insert = $connection->prepare(
            "INSERT INTO category_media_map (category_slug, media_slug, updated_by)
             VALUES (:category_slug, :media_slug, :updated_by)
             ON DUPLICATE KEY UPDATE updated_by = VALUES(updated_by)"
        );
        foreach (array_unique($categorySlugs) as $categorySlug) {
            $parameters = [
                'category_slug' => $categorySlug,
                'media_slug' => $mediaSlug,
                'updated_by' => $updatedBy,
            ];
            $insert->execute($parameters);
        }
    }
}
