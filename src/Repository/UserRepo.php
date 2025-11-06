<?php

namespace App\Repository;

use App\Domain\NotFoundException;
use App\Infra\Db;
use PDO;

class UserRepo
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
