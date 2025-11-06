<?php

namespace App\Repository;

use App\Domain\Enums;
use App\Domain\NotFoundException;
use App\Infra\Db;
use PDO;

class WorkOrderRepo
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    public function createFromTicket(array $ticket, ?int $dispatcherId = null): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO work_order (ticket_id, asset_id, priority, assignee_id, status, sla_start, sla_deadline, labor_minutes, result, created_at) VALUES (:ticket_id, :asset_id, :priority, NULL, :status, NULL, NULL, 0, NULL, :created_at)');
        $stmt->execute([
            ':ticket_id' => $ticket['id'],
            ':asset_id' => $ticket['asset_id'],
            ':priority' => $ticket['severity'] ?? 0,
            ':status' => Enums::WORK_ORDER_STATUS_PENDING,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->findById((int)$this->pdo->lastInsertId());
    }

    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM work_order WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $workOrder = $stmt->fetch();
        if (!$workOrder) {
            throw new NotFoundException('Work order not found');
        }
        return $workOrder;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE work_order SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function updateAssignment(int $id, int $assigneeId, ?string $eta): void
    {
        $stmt = $this->pdo->prepare('UPDATE work_order SET assignee_id = :assignee_id, sla_deadline = :eta WHERE id = :id');
        $stmt->execute([':assignee_id' => $assigneeId, ':eta' => $eta, ':id' => $id]);
    }

    public function touchSlaStart(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE work_order SET sla_start = COALESCE(sla_start, :now) WHERE id = :id');
        $stmt->execute([':now' => date('Y-m-d H:i:s'), ':id' => $id]);
    }

    public function updateLaborAndResult(int $id, int $laborMinutes, ?string $result, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE work_order SET labor_minutes = :labor, result = :result, status = :status WHERE id = :id');
        $stmt->execute([
            ':labor' => $laborMinutes,
            ':result' => $result,
            ':status' => $status,
            ':id' => $id,
        ]);
    }

    public function updateAcceptance(int $id, string $status, string $result): void
    {
        $stmt = $this->pdo->prepare('UPDATE work_order SET status = :status, result = :result WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':result' => $result,
            ':id' => $id,
        ]);
    }

    public function countByStatus(array $statuses): int
    {
        $in = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM work_order WHERE status IN ({$in})");
        $stmt->execute($statuses);
        return (int)$stmt->fetchColumn();
    }

    public function countOverdue(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM work_order WHERE status != :closed AND sla_deadline IS NOT NULL AND sla_deadline < :now");
        $stmt->execute([':closed' => Enums::WORK_ORDER_STATUS_CLOSED, ':now' => date('Y-m-d H:i:s')]);
        return (int)$stmt->fetchColumn();
    }

    public function fetchClosed(): array
    {
        $stmt = $this->pdo->prepare("SELECT id, sla_deadline, result FROM work_order WHERE status = :status");
        $stmt->execute([':status' => Enums::WORK_ORDER_STATUS_CLOSED]);
        return $stmt->fetchAll();
    }

    public function sumLaborByAssignee(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT SUM(labor_minutes) FROM work_order WHERE assignee_id = :assignee');
        $stmt->execute([':assignee' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
