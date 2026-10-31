<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class NotificationSettings extends Model
{
    protected string $table = 'Notification_settings';

    public const FIELDS = [
        'daily_reminder' => 'daily_reminder',
        'streak_end_reminder' => 'streak_end_reminder',
        'push_notification' => 'push_notification',
        'lunar_notification' => 'lunar_notification',
    ];

    public function findForUser(int $userId): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT app_user_id, daily_reminder, streak_end_reminder,
                    push_notification, lunar_notification, added_date_time, updated_date_time
             FROM {$this->table} WHERE app_user_id = :app_user_id LIMIT 1"
        );
        $statement->execute(['app_user_id' => $userId]);
        $settings = $statement->fetch();

        return $settings !== false ? $settings : null;
    }

    public function saveForUser(int $userId, array $fields): void
    {
        $columns = ['app_user_id'];
        $placeholders = [':app_user_id'];
        $updates = [];
        $parameters = ['app_user_id' => $userId];

        foreach (self::FIELDS as $input => $column) {
            if (!array_key_exists($input, $fields)) {
                continue;
            }
            $columns[] = '`' . $column . '`';
            $placeholders[] = ':' . $input;
            $updates[] = '`' . $column . '` = VALUES(`' . $column . '`)';
            $parameters[$input] = $fields[$input] ? 1 : 0;
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ')
                VALUES (' . implode(', ', $placeholders) . ')
                ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
        $this->db->connection()->prepare($sql)->execute($parameters);
    }
}
