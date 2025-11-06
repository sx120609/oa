<?php

namespace App\Service;

use App\Domain\Enums;
use App\Repository\InventoryRepo;
use App\Repository\WorkOrderRepo;

class ReportService
{
    private WorkOrderRepo $workOrders;
    private InventoryRepo $inventory;

    public function __construct(WorkOrderRepo $workOrders, InventoryRepo $inventory)
    {
        $this->workOrders = $workOrders;
        $this->inventory = $inventory;
    }

    public function dashboard(?int $userId = null): array
    {
        $inProgress = $this->workOrders->countByStatus([
            Enums::WORK_ORDER_STATUS_IN_PROGRESS,
            Enums::WORK_ORDER_STATUS_PAUSED,
        ]);
        $overdue = $this->workOrders->countOverdue();
        $closed = $this->workOrders->fetchClosed();
        $closedCount = count($closed);
        $onTime = 0;
        $now = new \DateTimeImmutable();
        foreach ($closed as $order) {
            $payload = json_decode($order['result'] ?? '[]', true) ?: [];
            $qa = $payload['qa'] ?? [];
            $closedAt = isset($qa['closed_at']) ? new \DateTimeImmutable($qa['closed_at']) : $now;
            if (empty($order['sla_deadline'])) {
                $onTime++;
                continue;
            }
            $deadline = new \DateTimeImmutable($order['sla_deadline']);
            if ($closedAt <= $deadline) {
                $onTime++;
            }
        }
        $slaRate = $closedCount > 0 ? round(($onTime / $closedCount) * 100, 2) : 100.0;
        $topRaw = $this->inventory->sumIssuedBySpare();
        $top = [];
        foreach ($topRaw as $row) {
            $top[] = [
                'spare_id' => (int)$row['spare_id'],
                'name' => $row['name'],
                'total_qty' => (int)$row['total_qty'],
            ];
        }
        $labor = 0;
        if ($userId) {
            $labor = $this->workOrders->sumLaborByAssignee($userId);
        }

        return [
            'in_progress_count' => $inProgress,
            'overdue_count' => $overdue,
            'sla_rate' => $slaRate,
            'top_spares' => $top,
            'my_labor_minutes' => $labor,
        ];
    }
}
