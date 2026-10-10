<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'ju_users';

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
}
