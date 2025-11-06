<?php

namespace App\Controller;

use App\Domain\Enums;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Service\InventoryService;

class InventoryController
{
    private InventoryService $inventory;

    public function __construct(InventoryService $inventory)
    {
        $this->inventory = $inventory;
    }

    public function listSpares(Request $request)
    {
        AuthMiddleware::requireRole($request, Enums::ROLES);
        $term = $request->getQuery('q');
        return $this->inventory->listSpares($term);
    }

    public function transact(Request $request)
    {
        AuthMiddleware::requireRole($request, [Enums::ROLE_WH]);
        $payload = $request->getJsonBody() ?? [];
        return $this->inventory->recordTransaction($payload);
    }
}
