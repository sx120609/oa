<?php

namespace App\Controller;

use App\Domain\Enums;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Service\TicketService;

class TicketController
{
    private TicketService $tickets;

    public function __construct(TicketService $tickets)
    {
        $this->tickets = $tickets;
    }

    public function index(Request $request)
    {
        AuthMiddleware::requireRole($request, Enums::ROLES);
        $status = $request->getQuery('status');
        return $this->tickets->list($status);
    }

    public function create(Request $request)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_DISPATCHER, Enums::ROLE_VIEWER]);
        $data = $request->getJsonBody() ?? [];
        return $this->tickets->create($data);
    }
}
