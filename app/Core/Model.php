<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(protected readonly Database $db)
    {
    }

    public function all(): array
    {
        $statement = $this->db->connection()->query("SELECT * FROM {$this->table}");
        return $statement->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $statement = $this->db->connection()->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $result = $statement->fetch();
        return $result !== false ? $result : null;
    }
}
