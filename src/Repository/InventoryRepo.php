<?php

namespace App\Repository;

use App\Domain\ConflictException;
use App\Infra\Db;
use PDO;

class InventoryRepo
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    public function searchSpares(?string $term = null): array
    {
        if ($term) {
            $like = '%' . $term . '%';
            $stmt = $this->pdo->prepare('SELECT s.*, i.qty_available FROM spare_item s LEFT JOIN inventory i ON s.id = i.spare_id WHERE s.name LIKE :term OR s.code LIKE :term ORDER BY s.name');
            $stmt->execute([':term' => $like]);
        } else {
            $stmt = $this->pdo->query('SELECT s.*, i.qty_available FROM spare_item s LEFT JOIN inventory i ON s.id = i.spare_id ORDER BY s.name');
        }
        return $stmt->fetchAll();
    }

    public function lockInventoryRow(int $spareId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventory WHERE spare_id = :spare_id FOR UPDATE');
        $stmt->execute([':spare_id' => $spareId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ConflictException('Inventory record missing for spare', ['spare_id' => $spareId]);
        }
        return $row;
    }

    public function updateInventory(int $inventoryId, int $qty): void
    {
        $stmt = $this->pdo->prepare('UPDATE inventory SET qty_available = :qty WHERE id = :id');
        $stmt->execute([':qty' => $qty, ':id' => $inventoryId]);
    }

    public function recordTransaction(int $spareId, int $workOrderId, int $qty, string $type): array
    {
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO inv_txn (spare_id, work_order_id, qty, type, created_at) VALUES (:spare_id, :work_order_id, :qty, :type, :created_at)');
        $stmt->execute([
            ':spare_id' => $spareId,
            ':work_order_id' => $workOrderId,
            ':qty' => $qty,
            ':type' => $type,
            ':created_at' => $createdAt,
        ]);
        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'spare_id' => $spareId,
            'work_order_id' => $workOrderId,
            'qty' => $qty,
            'type' => $type,
            'created_at' => $createdAt,
        ];
    }

    public function sumIssuedBySpare(): array
    {
        $stmt = $this->pdo->query("SELECT s.id AS spare_id, s.name, SUM(t.qty) AS total_qty FROM inv_txn t JOIN spare_item s ON t.spare_id = s.id WHERE t.type = 'issue' GROUP BY s.id, s.name ORDER BY total_qty DESC LIMIT 5");
        return $stmt->fetchAll();
    }

    public function hasTransactionsForWorkOrder(int $workOrderId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(1) FROM inv_txn WHERE work_order_id = :work_order_id');
        $stmt->execute([':work_order_id' => $workOrderId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
