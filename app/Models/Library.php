<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Library extends Model
{
    protected string $table = 'ju_library';

    public const MEDIA_FIELDS = ['youtube', 'link', 'audio', 'video', 'image'];

    public const URL_FIELDS = ['youtube', 'link'];

    public function search(string $term = '', string $status = 'all'): array
    {
        $conditions = [];
        $parameters = [];

        if ($term !== '') {
            $conditions[] = 'slug LIKE :slug';
            $parameters['slug'] = '%' . $term . '%';
        }

        if ($status === 'active' || $status === 'inactive') {
            $conditions[] = 'status = :status';
            $parameters['status'] = $status;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table}{$where} ORDER BY modified_at DESC, id DESC"
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

    /**
     * @param array<string,?string> $media
     */
    public function create(string $slug, array $media, string $status): void
    {
        $statement = $this->db->connection()->prepare(
            "INSERT INTO {$this->table} (slug, youtube, audio, video, link, image, status)
             VALUES (:slug, :youtube, :audio, :video, :link, :image, :status)"
        );
        $statement->execute($this->parameters($slug, $media, $status));
    }

    /**
     * @param array<string,?string> $media
     */
    public function update(int $id, string $slug, array $media, string $status): void
    {
        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table}
             SET slug = :slug, youtube = :youtube, audio = :audio, video = :video,
                 link = :link, image = :image, status = :status
             WHERE id = :id"
        );
        $statement->execute($this->parameters($slug, $media, $status) + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->db->connection()->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );
        $statement->execute(['id' => $id]);
    }

    private function parameters(string $slug, array $media, string $status): array
    {
        $parameters = ['slug' => $slug, 'status' => $status];
        foreach (self::MEDIA_FIELDS as $field) {
            $parameters[$field] = $media[$field] ?? null;
        }

        return $parameters;
    }
}
