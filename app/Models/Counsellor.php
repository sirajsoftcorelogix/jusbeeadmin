<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Counsellor extends Model
{
    protected string $table = 'Counsellors';

    public function search(string $term = ''): array
    {
        $where = '';
        $parameters = [];
        if ($term !== '') {
            $where = ' WHERE (c.consellor_name LIKE :name OR c.email LIKE :email OR c.expertise LIKE :expertise)';
            $search = '%' . $term . '%';
            $parameters = ['name' => $search, 'email' => $search, 'expertise' => $search];
        }
        $statement = $this->db->connection()->prepare(
            "SELECT c.*, editor.name AS updated_by_name
             FROM {$this->table} c
             LEFT JOIN ju_users editor ON editor.id = c.updated_by{$where}
             ORDER BY c.updated_date_time DESC, c.id DESC"
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
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function countAll(): int
    {
        return (int) $this->db->connection()->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    public function create(array $fields): int
    {
        $statement = $this->db->connection()->prepare(
            "INSERT INTO {$this->table} (
                consellor_name, profile_thumbnail, qualifications, email, phone,
                verifies, biography, expertise, standard_fee, discovery_fee, updated_by
             ) VALUES (
                :consellor_name, :profile_thumbnail, :qualifications, :email, :phone,
                :verifies, :biography, :expertise, :standard_fee, :discovery_fee, :updated_by
             )"
        );
        $statement->execute($fields);

        return (int) $this->db->connection()->lastInsertId();
    }

    public function update(int $id, array $fields): void
    {
        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table}
             SET consellor_name = :consellor_name, profile_thumbnail = :profile_thumbnail,
                 qualifications = :qualifications, email = :email, phone = :phone,
                 verifies = :verifies, biography = :biography, expertise = :expertise,
                 standard_fee = :standard_fee, discovery_fee = :discovery_fee, updated_by = :updated_by
             WHERE id = :id"
        );
        $statement->execute($fields + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->connection()->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute(['id' => $id]);
    }

    public function verifiedList(): array
    {
        $statement = $this->db->connection()->query(
            "SELECT id, consellor_name, profile_thumbnail, qualifications, email, phone,
                    verifies, biography, expertise, standard_fee, discovery_fee,
                    added_date_time, updated_date_time
             FROM {$this->table}
             WHERE verifies = 'Y'
             ORDER BY consellor_name, id"
        );

        return $statement->fetchAll();
    }

    public function findVerified(int $id): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT id, consellor_name, profile_thumbnail, qualifications, email, phone,
                    verifies, biography, expertise, standard_fee, discovery_fee,
                    added_date_time, updated_date_time
             FROM {$this->table} WHERE id = :id AND verifies = 'Y' LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }
}
