<?php

namespace App\Repository;

use App\Domain\Enums;
use App\Domain\NotFoundException;
use App\Infra\Db;
use PDO;

class TicketRepo
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO ticket (asset_id, symptom, severity, status, photos, created_at) VALUES (:asset_id, :symptom, :severity, :status, :photos, :created_at)');
        $stmt->execute([
            ':asset_id' => $data['asset_id'],
            ':symptom' => $data['symptom'],
            ':severity' => $data['severity'],
            ':status' => Enums::TICKET_STATUS_NEW,
            ':photos' => $data['photos'] ? json_encode($data['photos']) : null,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }

    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ticket WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            throw new NotFoundException('Ticket not found');
        }
        if (!empty($ticket['photos'])) {
            $ticket['photos'] = json_decode($ticket['photos'], true) ?: [];
        } else {
            $ticket['photos'] = [];
        }
        return $ticket;
    }

    public function list(?string $status = null): array
    {
        if ($status) {
            $stmt = $this->pdo->prepare('SELECT * FROM ticket WHERE status = :status ORDER BY created_at DESC');
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM ticket ORDER BY created_at DESC');
        }
        $tickets = $stmt->fetchAll();
        foreach ($tickets as &$ticket) {
            $ticket['photos'] = $ticket['photos'] ? json_decode($ticket['photos'], true) : [];
        }
        return $tickets;
    }

    public function markWorkOrderCreated(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE ticket SET status = :status WHERE id = :id');
        $stmt->execute([':status' => Enums::TICKET_STATUS_WO_CREATED, ':id' => $id]);
    }
}
