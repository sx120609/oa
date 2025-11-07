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

            $projects = $this->fetchProjects($pdo);
            $devices = $this->fetchDevices($pdo);
            $reservations = $this->fetchReservations($pdo);
            $checkouts = $this->fetchCheckouts($pdo);
            $notifications = $this->fetchNotifications($pdo);
            $users = $this->fetchUsers($pdo);
            $transfers = $this->fetchTransfers($pdo);

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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchProjects(PDO $pdo): array
    {
        try {
            $rows = $pdo->query('SELECT * FROM projects ORDER BY id ASC LIMIT 50')->fetchAll() ?: [];
        } catch (PDOException $exception) {
            error_log('API load projects failed: ' . $exception->getMessage());
            return [];
        }

        return array_map(static fn(array $row): array => [
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? null,
            'location' => $row['location'] ?? null,
            'status' => $row['status'] ?? null,
            'starts_at' => $row['starts_at'] ?? null,
            'due_at' => $row['due_at'] ?? null,
            'quote_amount' => $row['quote_amount'] ?? null,
            'note' => $row['note'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchDevices(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query(
                'SELECT d.*, u.name AS holder_name, u.email AS holder_email
                 FROM devices d
                 LEFT JOIN (
                    SELECT c.device_id, c.user_id
                    FROM checkouts c
                    WHERE c.return_at IS NULL
                    ORDER BY c.device_id, c.checked_out_at DESC
                 ) AS active ON active.device_id = d.id
                 LEFT JOIN users u ON u.id = active.user_id
                 ORDER BY d.id ASC
                 LIMIT 50'
            );
            $rows = $stmt ? $stmt->fetchAll() : [];
        } catch (PDOException $exception) {
            error_log('API load devices failed: ' . $exception->getMessage());
            return [];
        }

        return array_map(static fn(array $row): array => [
            'id' => $row['id'] ?? null,
            'code' => $row['code'] ?? null,
            'model' => $row['model'] ?? null,
            'status' => $row['status'] ?? null,
            'serial' => $row['serial'] ?? null,
            'photo_url' => $row['photo_url'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'holder_name' => $row['holder_name'] ?? null,
            'holder_email' => $row['holder_email'] ?? null,
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchReservations(PDO $pdo): array
    {
        try {
            $rows = $pdo->query(
                'SELECT r.*, p.name AS project_name, d.code AS device_code
                 FROM reservations r
                 LEFT JOIN projects p ON p.id = r.project_id
                 LEFT JOIN devices d ON d.id = r.device_id
                 ORDER BY r.id ASC
                 LIMIT 50'
            )->fetchAll() ?: [];
        } catch (PDOException $exception) {
            error_log('API load reservations failed: ' . $exception->getMessage());
            return [];
        }

        return array_map(static fn(array $row): array => [
            'id' => $row['id'] ?? null,
            'project_id' => $row['project_id'] ?? null,
            'device_id' => $row['device_id'] ?? null,
            'reserved_from' => $row['reserved_from'] ?? null,
            'reserved_to' => $row['reserved_to'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'project_name' => $row['project_name'] ?? null,
            'device_code' => $row['device_code'] ?? null,
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCheckouts(PDO $pdo): array
    {
        try {
            $rows = $pdo->query(
                'SELECT c.*, p.name AS project_name, d.code AS device_code
                 FROM checkouts c
                 LEFT JOIN projects p ON p.id = c.project_id
                 LEFT JOIN devices d ON d.id = c.device_id
                 ORDER BY c.id ASC
                 LIMIT 50'
            )->fetchAll() ?: [];
        } catch (PDOException $exception) {
            error_log('API load checkouts failed: ' . $exception->getMessage());
            return [];
        }

        return array_map(static fn(array $row): array => [
            'id' => $row['id'] ?? null,
            'project_id' => $row['project_id'] ?? null,
            'device_id' => $row['device_id'] ?? null,
            'user_id' => $row['user_id'] ?? null,
            'checked_out_at' => $row['checked_out_at'] ?? null,
            'due_at' => $row['due_at'] ?? null,
            'return_at' => $row['return_at'] ?? null,
            'note' => $row['note'] ?? null,
            'checkout_photo' => $row['checkout_photo'] ?? null,
            'return_photo' => $row['return_photo'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'project_name' => $row['project_name'] ?? null,
            'device_code' => $row['device_code'] ?? null,
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchNotifications(PDO $pdo): array
    {
        try {
            $rows = $pdo->query(
                'SELECT * FROM notifications ORDER BY id ASC LIMIT 50'
            )->fetchAll() ?: [];
        } catch (PDOException $exception) {
            error_log('API load notifications failed: ' . $exception->getMessage());
            return [];
        }

        return array_map(static fn(array $row): array => [
            'id' => $row['id'] ?? null,
            'user_id' => $row['user_id'] ?? null,
            'title' => $row['title'] ?? null,
            'body' => $row['body'] ?? null,
            'not_before' => $row['not_before'] ?? null,
            'delivered_at' => $row['delivered_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchUsers(PDO $pdo): array
    {
        try {
            return $pdo->query(
                'SELECT id, name, email, role, created_at FROM users ORDER BY id ASC LIMIT 50'
            )->fetchAll() ?: [];
        } catch (PDOException $exception) {
            error_log('API load users failed: ' . $exception->getMessage());
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchTransfers(PDO $pdo): array
    {
        try {
            return $pdo->query(
                'SELECT * FROM device_transfers ORDER BY id ASC LIMIT 50'
            )->fetchAll() ?: [];
        } catch (PDOException $exception) {
            error_log('API load transfers failed: ' . $exception->getMessage());
            return [];
        }
    }
}
