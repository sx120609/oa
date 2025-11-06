<?php

namespace App\Controller;

use App\Domain\Enums;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Service\ReportService;

class ReportController
{
    private ReportService $reports;

    public function __construct(ReportService $reports)
    {
        $this->reports = $reports;
    }

    public function dashboard(Request $request)
    {
        AuthMiddleware::requireRole($request, Enums::ROLES);
        $user = $request->getUser();
        return $this->reports->dashboard($user['id'] ?? null);
    }
}
