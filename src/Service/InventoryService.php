<?php

namespace App\Service;

use App\Domain\ConflictException;
use App\Domain\Enums;
use App\Domain\ValidationException;
use App\Infra\Db;
use App\Repository\InventoryRepo;
use App\Repository\WorkOrderRepo;
use App\Util\Helpers;

class InventoryService
{
    private InventoryRepo $inventory;
    private WorkOrderRepo $workOrders;

    public function __construct(InventoryRepo $inventory, WorkOrderRepo $workOrders)
    {
        $this->inventory = $inventory;
        $this->workOrders = $workOrders;
    }

    public function listSpares(?string $term = null): array
    {
        return $this->inventory->searchSpares($term);
    }

    public function recordTransaction(array $payload): array
    {
        Helpers::requireFields($payload, ['work_order_id', 'spare_id', 'qty', 'type']);
        $workOrderId = Helpers::intVal($payload['work_order_id'], 'work_order_id');
        $spareId = Helpers::intVal($payload['spare_id'], 'spare_id');
        $qty = Helpers::intVal($payload['qty'], 'qty');
        if ($qty <= 0) {
            throw new ValidationException('Quantity must be positive');
        }
        $type = $payload['type'];
        if (!in_array($type, [Enums::INVENTORY_TXN_ISSUE, Enums::INVENTORY_TXN_RETURN], true)) {
            throw new ValidationException('Invalid inventory transaction type');
        }
        $workOrder = $this->workOrders->findById($workOrderId);
        if ($workOrder['status'] === Enums::WORK_ORDER_STATUS_CLOSED) {
            throw new ConflictException('Closed work order cannot request materials');
        }
        return Db::transaction(function () use ($spareId, $qty, $type, $workOrderId) {
            $row = $this->inventory->lockInventoryRow($spareId);
            $available = (int)$row['qty_available'];
            if ($type === Enums::INVENTORY_TXN_ISSUE) {
                if ($available < $qty) {
                    throw new ConflictException('Insufficient stock', ['available' => $available]);
                }
                $available -= $qty;
            } else {
                $available += $qty;
            }
            $this->inventory->updateInventory((int)$row['id'], $available);
            $transaction = $this->inventory->recordTransaction($spareId, $workOrderId, $qty, $type);
            return [
                'transaction_id' => $transaction['id'],
                'work_order_id' => $workOrderId,
                'spare_id' => $spareId,
                'type' => $type,
                'qty' => $qty,
                'created_at' => $transaction['created_at'],
                'qty_available' => $available,
            ];
        });
    }
}
