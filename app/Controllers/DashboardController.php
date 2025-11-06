<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\DB;
use App\Utils\Response;
use PDO;
use PDOException;

final class DashboardController extends Controller
{
    public function summary(): string
    {
        try {
            $pdo = DB::connection();
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $projects = $pdo->query(
                'SELECT id, name, location, status, starts_at, due_at, quote_amount, note, created_at
                 FROM projects ORDER BY created_at DESC LIMIT 10'
            )->fetchAll() ?: [];

            $devices = $pdo->query(
                'SELECT id, code, model, status, serial, photo_url, created_at
                 FROM devices ORDER BY created_at DESC LIMIT 10'
            )->fetchAll() ?: [];

            $reservations = $pdo->query(
                'SELECT r.id, r.project_id, r.device_id, r.reserved_from, r.reserved_to, r.created_at,
                        p.name AS project_name, d.code AS device_code
                 FROM reservations r
                 LEFT JOIN projects p ON p.id = r.project_id
                 LEFT JOIN devices d ON d.id = r.device_id
                 ORDER BY r.reserved_from DESC
                 LIMIT 10'
            )->fetchAll() ?: [];

            $checkouts = $pdo->query(
                'SELECT c.id, c.project_id, c.device_id, c.user_id, c.checked_out_at, c.due_at, c.return_at, c.note, c.created_at,
                        p.name AS project_name, d.code AS device_code
                 FROM checkouts c
                 LEFT JOIN projects p ON p.id = c.project_id
                 LEFT JOIN devices d ON d.id = c.device_id
                 ORDER BY c.checked_out_at DESC
                 LIMIT 10'
            )->fetchAll() ?: [];

            $notifications = $pdo->query(
                'SELECT id, user_id, title, body, not_before, delivered_at, created_at
                 FROM notifications
                 ORDER BY created_at DESC
                 LIMIT 10'
            )->fetchAll() ?: [];

            $users = $pdo->query(
                'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 20'
            )->fetchAll() ?: [];

            $transfers = $pdo->query(
                'SELECT id, device_id, from_checkout_id, from_user_id, to_user_id, target_project_id, target_due_at,
                        transfer_type, status, note, requested_at, confirmed_at
                 FROM device_transfers
                 ORDER BY requested_at DESC
                 LIMIT 20'
            )->fetchAll() ?: [];

            $payload = [
                'projects' => $projects,
                'devices' => $devices,
                'reservations' => $reservations,
                'checkouts' => $checkouts,
                'notifications' => $notifications,
                'users' => $users,
                'transfers' => $transfers,
            ];

            header('Content-Type: application/json');
            return (string) json_encode([
                'success' => true,
                'data' => $payload,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (PDOException $exception) {
            header('Content-Type: application/json', true, 500);
            return (string) json_encode([
                'success' => false,
                'message' => '数据加载失败：' . $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
    }
}
