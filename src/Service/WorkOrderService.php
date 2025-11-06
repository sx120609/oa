<?php

namespace App\Service;

use App\Domain\AuthorizationException;
use App\Domain\ConflictException;
use App\Domain\Enums;
use App\Domain\ValidationException;
use App\Infra\Db;
use App\Infra\Logger;
use App\Repository\InventoryRepo;
use App\Repository\TicketRepo;
use App\Repository\WorkOrderRepo;
use App\Util\Helpers;

class WorkOrderService
{
    private WorkOrderRepo $workOrders;
    private TicketRepo $tickets;
    private InventoryRepo $inventory;

    public function __construct(WorkOrderRepo $workOrders, TicketRepo $tickets, InventoryRepo $inventory)
    {
        $this->workOrders = $workOrders;
        $this->tickets = $tickets;
        $this->inventory = $inventory;
    }

    public function createFromTicket(int $ticketId): array
    {
        $ticket = $this->tickets->findById($ticketId);
        if ($ticket['status'] !== Enums::TICKET_STATUS_NEW) {
            throw new ConflictException('Ticket already processed');
        }
        $workOrder = $this->workOrders->createFromTicket($ticket);
        $this->tickets->markWorkOrderCreated($ticketId);
        return $workOrder;
    }

    public function assign(int $workOrderId, int $assigneeId, ?string $eta): array
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        if ($workOrder['status'] === Enums::WORK_ORDER_STATUS_CLOSED) {
            throw new ConflictException('Cannot assign closed work order');
        }
        $this->workOrders->updateAssignment($workOrderId, $assigneeId, $eta);
        return $this->workOrders->findById($workOrderId);
    }

    public function start(int $workOrderId, array $user): array
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        $this->assertTechOwns($workOrder, $user);
        if (!in_array($workOrder['status'], [Enums::WORK_ORDER_STATUS_PENDING, Enums::WORK_ORDER_STATUS_PAUSED], true)) {
            throw new ConflictException('Cannot start work order from current status');
        }
        Db::transaction(function () use ($workOrderId) {
            $this->workOrders->touchSlaStart($workOrderId);
            $this->workOrders->updateStatus($workOrderId, Enums::WORK_ORDER_STATUS_IN_PROGRESS);
        });
        return $this->workOrders->findById($workOrderId);
    }

    public function pause(int $workOrderId, array $user): array
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        $this->assertTechOwns($workOrder, $user);
        if ($workOrder['status'] !== Enums::WORK_ORDER_STATUS_IN_PROGRESS) {
            throw new ConflictException('Only in-progress work can be paused');
        }
        $this->workOrders->updateStatus($workOrderId, Enums::WORK_ORDER_STATUS_PAUSED);
        return $this->workOrders->findById($workOrderId);
    }

    public function resume(int $workOrderId, array $user): array
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        $this->assertTechOwns($workOrder, $user);
        if ($workOrder['status'] !== Enums::WORK_ORDER_STATUS_PAUSED) {
            throw new ConflictException('Only paused work can be resumed');
        }
        $this->workOrders->updateStatus($workOrderId, Enums::WORK_ORDER_STATUS_IN_PROGRESS);
        return $this->workOrders->findById($workOrderId);
    }

    public function complete(int $workOrderId, array $user, array $payload): array
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        $this->assertTechOwns($workOrder, $user);
        if ($workOrder['status'] !== Enums::WORK_ORDER_STATUS_IN_PROGRESS) {
            throw new ConflictException('Work order must be in progress to complete');
        }
        Helpers::requireFields($payload, ['labor_minutes', 'result']);
        $labor = Helpers::intVal($payload['labor_minutes'], 'labor_minutes');
        if ($labor <= 0) {
            throw new ValidationException('labor_minutes must be greater than zero');
        }
        $result = Helpers::stringVal($payload['result'], 'result');
        $resultPayload = [
            'tech_result' => $result,
            'labor_minutes' => $labor,
            'completed_at' => date('c'),
        ];
        $this->workOrders->updateLaborAndResult(
            $workOrderId,
            $labor,
            json_encode($resultPayload, JSON_UNESCAPED_UNICODE),
            Enums::WORK_ORDER_STATUS_PENDING_QA
        );
        Logger::info('Work order completed awaiting QA', ['work_order_id' => $workOrderId]);
        return $this->workOrders->findById($workOrderId);
    }

    public function acceptance(int $workOrderId, array $payload, array $user): array
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        if ($workOrder['status'] !== Enums::WORK_ORDER_STATUS_PENDING_QA) {
            throw new ConflictException('Work order not awaiting QA');
        }
        $existing = json_decode($workOrder['result'] ?? '[]', true) ?: [];
        Helpers::requireFields($payload, ['passed']);
        $passed = Helpers::boolVal($payload['passed'], 'passed');
        $score = isset($payload['score']) ? Helpers::intVal($payload['score'], 'score') : null;
        $remarks = isset($payload['remarks']) ? Helpers::stringVal($payload['remarks'], 'remarks') : null;
        $materialsConfirmed = isset($payload['materials_confirmed'])
            ? Helpers::boolVal($payload['materials_confirmed'], 'materials_confirmed')
            : false;
        $photos = [];
        if (!empty($payload['photos'])) {
            if (!is_array($payload['photos'])) {
                throw new ValidationException('photos must be array');
            }
            $photos = $payload['photos'];
        }
        $existing['qa'] = [
            'passed' => $passed,
            'score' => $score,
            'remarks' => $remarks,
            'photos' => $photos,
            'qa_user' => $user['username'] ?? null,
            'closed_at' => date('c'),
        ];
        $status = $passed ? Enums::WORK_ORDER_STATUS_CLOSED : Enums::WORK_ORDER_STATUS_IN_PROGRESS;
        if ($status === Enums::WORK_ORDER_STATUS_CLOSED) {
            if ((int)$workOrder['labor_minutes'] <= 0) {
                throw new ConflictException('Labor must be recorded before closing the work order');
            }
            $hasMaterials = $this->inventory->hasTransactionsForWorkOrder($workOrderId);
            if (!$hasMaterials && !$materialsConfirmed) {
                throw new ConflictException('Material usage must be confirmed before closing', ['materials_recorded' => false]);
            }
            $existing['qa']['materials_recorded'] = $hasMaterials;
            $existing['qa']['materials_confirmed'] = $materialsConfirmed;
        }
        if ($status === Enums::WORK_ORDER_STATUS_IN_PROGRESS) {
            // allow rework to resume
            $this->workOrders->updateLaborAndResult(
                $workOrderId,
                (int)$workOrder['labor_minutes'],
                json_encode($existing, JSON_UNESCAPED_UNICODE),
                $status
            );
        } else {
            $this->workOrders->updateAcceptance($workOrderId, $status, json_encode($existing, JSON_UNESCAPED_UNICODE));
        }
        return $this->workOrders->findById($workOrderId);
    }

    private function assertTechOwns(array $workOrder, array $user): void
    {
        if (!$workOrder['assignee_id']) {
            throw new ConflictException('Work order not assigned');
        }
        if ((int)$workOrder['assignee_id'] !== (int)$user['id']) {
            throw new AuthorizationException('Work order assigned to another technician');
        }
    }
}
