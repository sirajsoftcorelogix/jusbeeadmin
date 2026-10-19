<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'ju_users';

    public function all(): array
    {
        $statement = $this->db->connection()->query(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC, id DESC"
        );

        return $statement->fetchAll();
    }

    public function search(string $term, string $status = 'all'): array
    {
        $conditions = [];
        $parameters = [];

        if ($term !== '') {
            $conditions[] = '(name LIKE :name OR email LIKE :email)';
            $parameters['name'] = '%' . $term . '%';
            $parameters['email'] = '%' . $term . '%';
        }

        if ($status === 'active' || $status === 'inactive') {
            $conditions[] = 'is_active = :is_active';
            $parameters['is_active'] = $status === 'active' ? 1 : 0;
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
        return (int) $this->db->connection()->query(
            "SELECT COUNT(*) FROM {$this->table}"
        )->fetchColumn();
    }

    public function activeAdminCount(): int
    {
        $statement = $this->db->connection()->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE role = 'admin' AND is_active = 1"
        );

        return (int) $statement->fetchColumn();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1"
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user !== false ? $user : null;
    }

    public function create(string $name, string $email, string $password, string $role): void
    {
        $statement = $this->db->connection()->prepare(
            "INSERT INTO {$this->table} (name, email, password, role) VALUES (:name, :email, :password, :role)"
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
    }

    public function updateDetails(int $id, string $name, string $email, string $role, ?string $password = null): void
    {
        $fields = ['name = :name', 'email = :email', 'role = :role'];
        $parameters = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ];

        if ($password !== null && $password !== '') {
            $fields[] = 'password = :password';
            $parameters['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . ' WHERE id = :id'
        );
        $statement->execute($parameters);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->connection()->prepare(
            "UPDATE {$this->table} SET is_active = :is_active WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }
}
