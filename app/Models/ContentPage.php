<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ContentPage extends Model
{
    protected string $table = 'ju_content_pages';

    public function search(string $term = '', string $status = 'all'): array
    {
        $conditions = [];
        $parameters = [];

        if ($term !== '') {
            $conditions[] = '(title LIKE :title OR slug LIKE :slug)';
            $parameters['title'] = '%' . $term . '%';
            $parameters['slug'] = '%' . $term . '%';
        }

        if ($status === 'draft' || $status === 'published') {
            $conditions[] = 'status = :status';
            $parameters['status'] = $status;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table}{$where} ORDER BY updated_at DESC, id DESC"
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

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE slug = :slug AND status = 'published' LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $page = $statement->fetch();

        return $page !== false ? $page : null;
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

    public function create(string $title, string $slug, string $body, string $status): void
    {
        $statement = $this->db->connection()->prepare(
            "INSERT INTO {$this->table} (title, slug, body, status, published_at)
             VALUES (:title, :slug, :body, :status, :published_at)"
        );
        $statement->execute([
            'title' => $title,
            'slug' => $slug,
            'body' => $body,
            'status' => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function update(int $id, string $title, string $slug, string $body, string $status): void
    {
        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table}
             SET title = :title, slug = :slug, body = :body, status = :status,
                 published_at = :published_at
             WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'body' => $body,
            'status' => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function setStatus(int $id, string $status): void
    {
        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table}
             SET status = :status, published_at = :published_at
             WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'status' => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->db->connection()->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );
        $statement->execute(['id' => $id]);
    }
}
