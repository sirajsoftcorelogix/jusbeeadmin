<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Riyaz extends Model
{
    protected string $table = 'Riyaz';

    public function all(): array
    {
        $statement = $this->db->connection()->query(
            "SELECT r.*, library.title AS media_title, editor.name AS updated_by_name
             FROM {$this->table} r
             JOIN ju_library library ON library.id = r.media_id
             LEFT JOIN ju_users editor ON editor.id = r.updated_by
             ORDER BY r.updated_date_time DESC, r.id DESC"
        );

        return $statement->fetchAll();
    }

    public function mediaOptions(): array
    {
        return $this->db->connection()->query(
            "SELECT id, title, slug FROM ju_library ORDER BY title, id"
        )->fetchAll();
    }

    public function create(int $mediaId, string $syncedJson, int $updatedBy): int
    {
        $statement = $this->db->connection()->prepare(
            "INSERT INTO {$this->table} (media_id, synced_json, updated_by)
             VALUES (:media_id, :synced_json, :updated_by)"
        );
        $statement->execute([
            'media_id' => $mediaId,
            'synced_json' => $syncedJson,
            'updated_by' => $updatedBy,
        ]);

        return (int) $this->db->connection()->lastInsertId();
    }

    public function update(int $id, int $mediaId, string $syncedJson, int $updatedBy): void
    {
        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table}
             SET media_id = :media_id, synced_json = :synced_json, updated_by = :updated_by
             WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'media_id' => $mediaId,
            'synced_json' => $syncedJson,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->connection()->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute(['id' => $id]);
    }
}
