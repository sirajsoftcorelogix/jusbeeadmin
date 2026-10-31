<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class JournalLog extends Model
{
    protected string $table = 'journal_log';

    public const TYPES = ['journal', 'graditude', 'dialy_dream', 'worry_tree'];

    public function listForUser(int $userId, string $type): array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT id, type, app_user_id, title, description, favorite, added_date_time
             FROM {$this->table}
             WHERE app_user_id = :app_user_id AND type = :type
             ORDER BY added_date_time DESC, id DESC"
        );
        $statement->execute(['app_user_id' => $userId, 'type' => $type]);

        return $statement->fetchAll();
    }

    public function createForUser(
        int $userId,
        string $type,
        string $title,
        string $description,
        bool $favorite
    ): int {
        $connection = $this->db->connection();
        $statement = $connection->prepare(
            "INSERT INTO {$this->table} (type, app_user_id, title, description, favorite)
             VALUES (:type, :app_user_id, :title, :description, :favorite)"
        );
        $statement->execute([
            'type' => $type,
            'app_user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'favorite' => $favorite ? 1 : 0,
        ]);

        return (int) $connection->lastInsertId();
    }
}
