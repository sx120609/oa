<?php

namespace App;

use App\Controller\AssetController;
use App\Controller\AttachmentController;
use App\Controller\AuthController;
use App\Controller\InventoryController;
use App\Controller\ReportController;
use App\Controller\TicketController;
use App\Controller\WorkOrderController;
use App\Http\Request;
use App\Http\Response;
use App\Infra\Db;
use App\Infra\Logger;
use App\Middleware\AuthMiddleware;
use App\Middleware\ErrorHandler;
use App\Middleware\JsonBody;
use App\Repository\InventoryRepo;
use App\Repository\TicketRepo;
use App\Repository\UserRepo;
use App\Repository\WorkOrderRepo;
use App\Service\InventoryService;
use App\Service\ReportService;
use App\Service\TicketService;
use App\Service\WorkOrderService;

class App
{
    private Router $router;

    public function __construct(array $config)
    {
        Db::configure($config['db']);
        Logger::configure($config['app']['log_level'] ?? 'info');

        $this->router = new Router();

        $ticketRepo = new TicketRepo();
        $workOrderRepo = new WorkOrderRepo();
        $inventoryRepo = new InventoryRepo();
        $userRepo = new UserRepo();

        AuthMiddleware::configure($config['app']['key'] ?? 'demo-key', $userRepo);

        $ticketService = new TicketService($ticketRepo);
        $workOrderService = new WorkOrderService($workOrderRepo, $ticketRepo);
        $inventoryService = new InventoryService($inventoryRepo, $workOrderRepo);
        $reportService = new ReportService($workOrderRepo, $inventoryRepo);

        $ticketController = new TicketController($ticketService);
        $workOrderController = new WorkOrderController($workOrderService);
        $inventoryController = new InventoryController($inventoryService);
        $reportController = new ReportController($reportService);
        $authController = new AuthController($userRepo);
        $attachmentController = new AttachmentController();
        $assetController = new AssetController();

        $this->router->add('POST', '/auth/login', [$authController, 'login']);

        $this->router->add('GET', '/tickets', [$ticketController, 'index']);
        $this->router->add('POST', '/tickets', [$ticketController, 'create']);

        $this->router->add('POST', '/work-orders', [$workOrderController, 'create']);
        $this->router->add('POST', '/work-orders/{id}/assign', [$workOrderController, 'assign']);
        $this->router->add('POST', '/work-orders/{id}/start', [$workOrderController, 'start']);
        $this->router->add('POST', '/work-orders/{id}/pause', [$workOrderController, 'pause']);
        $this->router->add('POST', '/work-orders/{id}/resume', [$workOrderController, 'resume']);
        $this->router->add('POST', '/work-orders/{id}/complete', [$workOrderController, 'complete']);
        $this->router->add('POST', '/work-orders/{id}/acceptance', [$workOrderController, 'acceptance']);

        $this->router->add('GET', '/spares', [$inventoryController, 'listSpares']);
        $this->router->add('POST', '/inventory/transactions', [$inventoryController, 'transact']);

        $this->router->add('GET', '/reports/dashboard', [$reportController, 'dashboard']);

        $this->router->add('POST', '/attachments', [$attachmentController, 'upload']);
        $this->router->add('GET', '/assets/{id}/qrcode', [$assetController, 'qrcode']);
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        ErrorHandler::handle(function () use ($request) {
            JsonBody::parse($request);
            AuthMiddleware::authenticate($request);
            $result = $this->router->dispatch($request);
            Response::json(['data' => $result]);
        });
    }
}
