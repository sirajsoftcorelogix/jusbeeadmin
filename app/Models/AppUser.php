<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AppUser extends Model
{
    protected string $table = 'ju_app_users';

    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy('email', $email);
    }

    public function findByPhone(string $phone): ?array
    {
        return $this->findOneBy('phone', $phone);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        return $this->exists('email', $email, $excludeId);
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        return $this->exists('phone', $phone, $excludeId);
    }

    public function create(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $passwordHash,
        array $profile = []
    ): int {
        $connection = $this->db->connection();
        $statement = $connection->prepare(
            "INSERT INTO {$this->table} (
                first_name, last_name, email, phone, password, profile_thumbnail,
                device_id, current_stage, birth_date, reduce_stress, sleep_better,
                build_heithy_habits, journal_throughts, find_professional_support
             ) VALUES (
                :first_name, :last_name, :email, :phone, :password, :profile_thumbnail,
                :device_id, :current_stage, :birth_date, :reduce_stress, :sleep_better,
                :build_heithy_habits, :journal_throughts, :find_professional_support
             )"
        );
        $statement->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $passwordHash,
            'profile_thumbnail' => $profile['profile_thumbnail'] ?? null,
            'device_id' => $profile['device_id'] ?? null,
            'current_stage' => $profile['current_stage'] ?? null,
            'birth_date' => $profile['birth_date'] ?? null,
            'reduce_stress' => $profile['reduce_stress'] ?? 0,
            'sleep_better' => $profile['sleep_better'] ?? 0,
            'build_heithy_habits' => $profile['build_heithy_habits'] ?? 0,
            'journal_throughts' => $profile['journal_throughts'] ?? 0,
            'find_professional_support' => $profile['find_professional_support'] ?? 0,
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @param array<string,mixed> $fields Subset of app-user columns accepted for updates.
     */
    public function update(int $id, array $fields): void
    {
        $allowed = [
            'first_name', 'last_name', 'email', 'phone', 'password',
            'profile_thumbnail', 'device_id', 'current_stage', 'birth_date',
            'reduce_stress', 'sleep_better', 'build_heithy_habits',
            'journal_throughts', 'find_professional_support', 'status',
        ];
        $sets = [];
        $parameters = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $fields)) {
                $sets[] = "{$column} = :{$column}";
                $parameters[$column] = $fields[$column];
            }
        }

        if ($sets === []) {
            return;
        }

        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . ' WHERE id = :id'
        );
        $statement->execute($parameters);
    }

    public function search(string $term = '', string $status = 'all'): array
    {
        $conditions = [];
        $parameters = [];
        if ($term !== '') {
            $conditions[] = '(first_name LIKE :first_name OR last_name LIKE :last_name OR email LIKE :email OR phone LIKE :phone)';
            $parameters['first_name'] = $parameters['last_name'] = $parameters['email'] = $parameters['phone'] = '%' . $term . '%';
        }
        if ($status === 'active' || $status === 'inactive') {
            $conditions[] = 'status = :status';
            $parameters['status'] = $status;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table}{$where} ORDER BY created_at DESC, id DESC"
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->connection()->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->db->connection()->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute(['id' => $id]);
    }

    public function touchLogin(int $id): void
    {
        $this->db->connection()->prepare(
            "UPDATE {$this->table} SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id"
        )->execute(['id' => $id]);
    }

    private function findOneBy(string $column, string $value): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1"
        );
        $statement->execute(['value' => $value]);
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    private function exists(string $column, string $value, ?int $excludeId): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$column} = :value";
        $parameters = ['value' => $value];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }

        $statement = $this->db->connection()->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }
}
