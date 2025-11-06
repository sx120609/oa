<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDOException;

final class ProjectController extends Controller
{
    public function create(): string
    {
        $actorId = $this->requireActor();

        $name = $this->requireString('name');
        $location = $this->requireString('location');

        $startsAt = $this->timestampFromPost('starts_at');
        $dueAt = $this->timestampFromPost('due_at');

        if ($startsAt !== null && $dueAt !== null && $startsAt > $dueAt) {
            throw new HttpException('time invalid', 409);
        }

        $quoteAmount = $this->decimalFromPost('quote_amount', 0.0);
        $note = $this->optionalString('note');

        $startsAtValue = date('Y-m-d H:i:s', $startsAt);
        $dueAtValue = date('Y-m-d H:i:s', $dueAt);
        $status = 'ongoing';

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare(
                'INSERT INTO projects (name, location, starts_at, due_at, quote_amount, note, status, created_by, created_at)
                 VALUES (:name, :location, :starts_at, :due_at, :quote_amount, :note, :status, :created_by, NOW())'
            );

            $stmt->execute([
                ':name' => $name,
                ':location' => $location,
                ':starts_at' => $startsAtValue,
                ':due_at' => $dueAtValue,
                ':quote_amount' => number_format($quoteAmount, 2, '.', ''),
                ':note' => $note,
                ':status' => $status,
                ':created_by' => $actorId,
            ]);

            $projectId = (int) $pdo->lastInsertId();
        } catch (PDOException $exception) {
            throw new HttpException('Unable to create project', 500, $exception);
        }

        AuditLogger::log(
            $actorId,
            'project',
            $projectId,
            'create',
            [
                'name' => $name,
                'location' => $location,
                'starts_at' => $startsAtValue,
                'due_at' => $dueAtValue,
                'quote_amount' => $quoteAmount,
                'status' => $status,
                'note' => $note,
                'created_by' => $actorId,
            ]
        );

        return Response::ok();
    }
}
