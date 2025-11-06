<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\DB;
use PDO;
use PDOException;
use function view;

final class HomeController extends Controller
{
    public function index(): string
    {
        $session = [
            'uid' => $_SESSION['uid'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
        ];

        $summary = [
            'users' => [],
            'projects' => [],
            'devices' => [],
            'reservations' => [],
            'checkouts' => [],
            'notifications' => [],
            'transfers' => [],
        ];

        $loadError = null;

        if ($session['uid']) {
            try {
                $pdo = DB::connection();
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $summary['projects'] = $pdo->query(
                    'SELECT id, name, location, status, starts_at, due_at, quote_amount, note, created_at
                     FROM projects ORDER BY created_at DESC LIMIT 50'
                )->fetchAll() ?: [];

                $summary['devices'] = $pdo->query(
                    'SELECT id, code, model, status, serial, photo_url, created_at
                     FROM devices ORDER BY created_at DESC LIMIT 50'
                )->fetchAll() ?: [];

                $summary['reservations'] = $pdo->query(
                    'SELECT r.id, r.project_id, r.device_id, r.reserved_from, r.reserved_to, r.created_at,
                            p.name AS project_name, d.code AS device_code
                     FROM reservations r
                     LEFT JOIN projects p ON p.id = r.project_id
                     LEFT JOIN devices d ON d.id = r.device_id
                     ORDER BY r.reserved_from DESC
                     LIMIT 50'
                )->fetchAll() ?: [];

                $summary['checkouts'] = $pdo->query(
                    'SELECT c.id, c.project_id, c.device_id, c.user_id, c.checked_out_at, c.due_at, c.return_at, c.note, c.created_at,
                            p.name AS project_name, d.code AS device_code
                     FROM checkouts c
                     LEFT JOIN projects p ON p.id = c.project_id
                     LEFT JOIN devices d ON d.id = c.device_id
                     ORDER BY c.checked_out_at DESC
                     LIMIT 50'
                )->fetchAll() ?: [];

                $summary['notifications'] = $pdo->query(
                    'SELECT id, user_id, title, body, not_before, delivered_at, created_at
                     FROM notifications
                     ORDER BY created_at DESC
                     LIMIT 50'
                )->fetchAll() ?: [];

                $summary['users'] = $pdo->query(
                    'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 50'
                )->fetchAll() ?: [];

                $summary['transfers'] = $pdo->query(
                    'SELECT id, device_id, from_checkout_id, from_user_id, to_user_id, target_project_id, target_due_at,
                            transfer_type, status, note, requested_at, confirmed_at
                     FROM device_transfers
                     ORDER BY requested_at DESC
                     LIMIT 50'
                )->fetchAll() ?: [];
            } catch (PDOException $exception) {
                $loadError = $exception->getMessage();
                error_log('Dashboard data load failed: ' . $exception->getMessage());
            }
        }

        return view('home', [
            'session' => $session,
            'data' => $summary,
            'loadError' => $loadError,
        ]);
    }
}
