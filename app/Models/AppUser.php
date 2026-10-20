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

    public function create(string $name, string $email, string $phone, string $passwordHash): int
    {
        $connection = $this->db->connection();
        $statement = $connection->prepare(
            "INSERT INTO {$this->table} (name, email, phone, password)
             VALUES (:name, :email, :phone, :password)"
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $passwordHash,
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @param array<string,string> $fields Subset of name, email, phone, password.
     */
    public function update(int $id, array $fields): void
    {
        $allowed = ['name', 'email', 'phone', 'password', 'status'];
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
            $conditions[] = '(name LIKE :name OR email LIKE :email OR phone LIKE :phone)';
            $parameters['name'] = $parameters['email'] = $parameters['phone'] = '%' . $term . '%';
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
