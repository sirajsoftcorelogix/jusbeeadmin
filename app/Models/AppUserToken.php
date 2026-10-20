<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AppUserToken extends Model
{
    protected string $table = 'ju_app_user_tokens';

    public const TTL_DAYS = 30;

    /** Stores only a SHA-256 hash of the token; returns the plain token and its expiry. */
    public function issue(int $userId, ?string $device = null): array
    {
        $connection = $this->db->connection();
        $connection->exec("DELETE FROM {$this->table} WHERE expires_at < CURRENT_TIMESTAMP");

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_DAYS * 86400);
        $connection->prepare(
            "INSERT INTO {$this->table} (user_id, token_hash, device, expires_at)
             VALUES (:user_id, :hash, :device, :expires_at)"
        )->execute([
            'user_id' => $userId,
            'hash' => hash('sha256', $token),
            'device' => $device !== null ? mb_substr($device, 0, 120) : null,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    /** Returns the token row if it exists and has not expired. */
    public function findValid(string $token): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE token_hash = :hash AND expires_at > :now LIMIT 1"
        );
        $statement->execute(['hash' => hash('sha256', $token), 'now' => date('Y-m-d H:i:s')]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function touch(int $id): void
    {
        $this->db->connection()->prepare(
            "UPDATE {$this->table} SET last_used_at = :now WHERE id = :id"
        )->execute(['now' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function revoke(int $id): void
    {
        $this->db->connection()->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute(['id' => $id]);
    }

    public function revokeAllForUser(int $userId, ?int $exceptId = null): void
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $parameters = ['user_id' => $userId];
        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $parameters['except_id'] = $exceptId;
        }
        $this->db->connection()->prepare($sql)->execute($parameters);
    }
}
