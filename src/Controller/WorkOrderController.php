<?php

namespace App\Controller;

use App\Domain\Enums;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Service\WorkOrderService;
use App\Util\Helpers;

class WorkOrderController
{
    private WorkOrderService $workOrders;

    public function __construct(WorkOrderService $workOrders)
    {
        $this->workOrders = $workOrders;
    }

    public function create(Request $request)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_DISPATCHER]);
        $payload = $request->getJsonBody() ?? [];
        Helpers::requireFields($payload, ['ticket_id']);
        $ticketId = Helpers::intVal($payload['ticket_id'], 'ticket_id');
        return $this->workOrders->createFromTicket($ticketId);
    }

    public function assign(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_DISPATCHER]);
        $payload = $request->getJsonBody() ?? [];
        Helpers::requireFields($payload, ['assignee_id']);
        $assignee = Helpers::intVal($payload['assignee_id'], 'assignee_id');
        $eta = $payload['eta'] ?? null;
        return $this->workOrders->assign($id, $assignee, $eta);
    }

    public function start(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_TECH]);
        return $this->workOrders->start($id, $request->getUser());
    }

    public function pause(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_TECH]);
        return $this->workOrders->pause($id, $request->getUser());
    }

    public function resume(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_TECH]);
        return $this->workOrders->resume($id, $request->getUser());
    }

    public function complete(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_TECH]);
        $payload = $request->getJsonBody() ?? [];
        return $this->workOrders->complete($id, $request->getUser(), $payload);
    }

    public function acceptance(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_DISPATCHER, Enums::ROLE_VIEWER]);
        $payload = $request->getJsonBody() ?? [];
        return $this->workOrders->acceptance($id, $payload, $request->getUser());
    }
}
