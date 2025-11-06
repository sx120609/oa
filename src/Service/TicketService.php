<?php

namespace App\Service;

use App\Domain\Enums;
use App\Domain\ValidationException;
use App\Repository\TicketRepo;
use App\Util\Helpers;

class TicketService
{
    private TicketRepo $tickets;

    public function __construct(TicketRepo $tickets)
    {
        $this->tickets = $tickets;
    }

    public function create(array $payload): array
    {
        Helpers::requireFields($payload, ['asset_id', 'symptom', 'severity']);
        $assetId = Helpers::intVal($payload['asset_id'], 'asset_id');
        $severity = Helpers::intVal($payload['severity'], 'severity');
        if ($severity < 1 || $severity > 3) {
            throw new ValidationException('Severity must be between 1 and 3');
        }
        $symptom = Helpers::stringVal($payload['symptom'], 'symptom');
        $photos = [];
        if (isset($payload['photos'])) {
            if (!is_array($payload['photos'])) {
                throw new ValidationException('photos must be array');
            }
            $photos = $payload['photos'];
        }
        return $this->tickets->create([
            'asset_id' => $assetId,
            'severity' => $severity,
            'symptom' => $symptom,
            'photos' => $photos,
        ]);
    }

    public function list(?string $status = null): array
    {
        return $this->tickets->list($status);
    }

    public function get(int $id): array
    {
        return $this->tickets->findById($id);
    }

    public function markWorkOrderCreated(int $ticketId): void
    {
        $this->tickets->markWorkOrderCreated($ticketId);
    }
}
