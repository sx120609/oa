<?php

namespace App\Repository;

use App\Infra\Db;
use PDO;

class SpareRepo
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM spare_item WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
