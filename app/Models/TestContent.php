<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TestContent extends Model
{
    protected string $table = 'test_content';

    public function search(string $term = ''): array
    {
        $parameters = [];
        $where = '';
        if ($term !== '') {
            $where = ' WHERE (content.title LIKE :title OR content.slug LIKE :slug)';
            $parameters = ['title' => '%' . $term . '%', 'slug' => '%' . $term . '%'];
        }
        $statement = $this->db->connection()->prepare(
            "SELECT content.*, editor.name AS updated_by_name,
                    (SELECT GROUP_CONCAT(category.category_name ORDER BY category.category_name SEPARATOR ', ')
                     FROM Category_content_map content_map
                     JOIN Content_Categories category ON category.slug = content_map.category_slug
                     WHERE content_map.content_slug = content.slug) AS category_names
             FROM {$this->table} content
             LEFT JOIN ju_users editor ON editor.id = content.updated_by{$where}
             ORDER BY content.updated_date_time DESC, content.id DESC"
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $content = $statement->fetch();

        return $content !== false ? $content : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $content = $statement->fetch();

        return $content !== false ? $content : null;
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
            $connection->prepare(
                "INSERT INTO {$this->table} (slug, title, short_test, full_text, thumb_nail, updated_by)
                 VALUES (:slug, :title, :short_test, :full_text, :thumb_nail, :updated_by)"
            )->execute($fields);
            $id = (int) $connection->lastInsertId();
            $this->syncCategories($connection, $fields['slug'], $categorySlugs, (int) $fields['updated_by']);
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
            $connection->prepare(
                "UPDATE {$this->table}
                 SET slug = :slug, title = :title, short_test = :short_test,
                     full_text = :full_text, thumb_nail = :thumb_nail, updated_by = :updated_by
                 WHERE id = :id"
            )->execute($fields + ['id' => $id]);
            $this->syncCategories($connection, $fields['slug'], $categorySlugs, (int) $fields['updated_by']);
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
        $this->db->connection()->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        )->execute(['id' => $id]);
    }

    public function categorySlugs(string $contentSlug): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT category_slug FROM Category_content_map WHERE content_slug = :content_slug"
        );
        $statement->execute(['content_slug' => $contentSlug]);

        return array_column($statement->fetchAll(), 'category_slug');
    }

    public function byCategorySlug(string $categorySlug): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT content.id, content.slug, content.title, content.short_test,
                    content.thumb_nail, content.added_date_time, content.updated_date_time
             FROM {$this->table} content
             INNER JOIN Category_content_map content_map ON content_map.content_slug = content.slug
             WHERE content_map.category_slug = :category_slug
             ORDER BY content.updated_date_time DESC, content.id DESC"
        );
        $statement->execute(['category_slug' => $categorySlug]);

        return $statement->fetchAll();
    }

    public function categoryNames(string $contentSlug): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT category.category_id, category.category_name, category.slug, category.parent_id
             FROM Category_content_map content_map
             INNER JOIN Content_Categories category ON category.slug = content_map.category_slug
             WHERE content_map.content_slug = :content_slug
             ORDER BY category.category_name, category.category_id"
        );
        $statement->execute(['content_slug' => $contentSlug]);

        return $statement->fetchAll();
    }

    private function syncCategories(\PDO $connection, string $contentSlug, array $categorySlugs, int $updatedBy): void
    {
        $categorySlugs = array_values(array_unique($categorySlugs));
        if ($categorySlugs === []) {
            $connection->prepare(
                "DELETE FROM Category_content_map WHERE content_slug = :content_slug"
            )->execute(['content_slug' => $contentSlug]);

            return;
        }

        $placeholders = [];
        $parameters = ['content_slug' => $contentSlug];
        foreach ($categorySlugs as $index => $slug) {
            $key = 'category_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $slug;
        }
        $connection->prepare(
            "DELETE FROM Category_content_map
             WHERE content_slug = :content_slug AND category_slug NOT IN (" . implode(', ', $placeholders) . ')'
        )->execute($parameters);

        $insert = $connection->prepare(
            "INSERT INTO Category_content_map (category_slug, content_slug, updated_by)
             VALUES (:category_slug, :content_slug, :updated_by)
             ON DUPLICATE KEY UPDATE updated_by = VALUES(updated_by)"
        );
        foreach ($categorySlugs as $categorySlug) {
            $insert->execute([
                'category_slug' => $categorySlug,
                'content_slug' => $contentSlug,
                'updated_by' => $updatedBy,
            ]);
        }
    }
}
