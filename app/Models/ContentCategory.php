<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ContentCategory extends Model
{
    protected string $table = 'Content_Categories';

    public function all(): array
    {
        $statement = $this->db->connection()->query(
            "SELECT category.*, parent.category_name AS parent_name, editor.name AS updated_by_name
             FROM {$this->table} category
             LEFT JOIN {$this->table} parent ON parent.category_id = category.parent_id
             LEFT JOIN ju_users editor ON editor.id = category.updated_by
             ORDER BY category.category_name, category.category_id"
        );

        return $statement->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE category_id = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $category = $statement->fetch();

        return $category !== false ? $category : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $category = $statement->fetch();

        return $category !== false ? $category : null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug";
        $parameters = ['slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND category_id != :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }
        $statement = $this->db->connection()->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function slugsExist(array $slugs): bool
    {
        $slugs = array_values(array_unique($slugs));
        if ($slugs === []) {
            return true;
        }
        $placeholders = [];
        $parameters = [];
        foreach ($slugs as $index => $slug) {
            $key = 'slug_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $slug;
        }
        $statement = $this->db->connection()->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE slug IN (" . implode(', ', $placeholders) . ')'
        );
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() === count($slugs);
    }

    public function parentWouldCreateCycle(int $categoryId, ?int $parentId): bool
    {
        while ($parentId !== null) {
            if ($parentId === $categoryId) {
                return true;
            }
            $parent = $this->find($parentId);
            if ($parent === null) {
                return false;
            }
            $parentId = $parent['parent_id'] !== null ? (int) $parent['parent_id'] : null;
        }

        return false;
    }

    public function create(array $fields): void
    {
        $this->db->connection()->prepare(
            "INSERT INTO {$this->table} (category_name, slug, parent_id, updated_by)
             VALUES (:category_name, :slug, :parent_id, :updated_by)"
        )->execute($fields);
    }

    public function update(int $id, array $fields): void
    {
        $this->db->connection()->prepare(
            "UPDATE {$this->table}
             SET category_name = :category_name, slug = :slug, parent_id = :parent_id, updated_by = :updated_by
             WHERE category_id = :category_id"
        )->execute($fields + ['category_id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->connection()->prepare(
            "DELETE FROM {$this->table} WHERE category_id = :category_id"
        )->execute(['category_id' => $id]);
    }
}
