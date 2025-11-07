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
    private const STATUSES = ['ongoing', 'done'];

    public function create(): string
    {
        $actorId = $this->requireActor();

        $name = $this->requireString('name');
        $location = $this->requireString('location');

        $startsAt = $this->timestampFromPost('starts_at');
        $dueAt = $this->timestampFromPost('due_at');

        if ($startsAt !== null && $dueAt !== null && $startsAt > $dueAt) {
            throw new HttpException('时间范围不合法', 409);
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
            throw new HttpException('项目创建失败', 500, $exception);
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

    public function update(): string
    {
        $actorId = $this->requireActor();

        $projectId = $this->requirePositiveInt('project_id');
        $name = $this->requireString('name');
        $location = $this->requireString('location');
        $status = strtolower($this->requireString('status'));

        if (!in_array($status, self::STATUSES, true)) {
            throw new HttpException('项目状态不合法', 409);
        }

        $startsAt = $this->timestampFromPost('starts_at');
        $dueAt = $this->timestampFromPost('due_at');

        if ($startsAt !== null && $dueAt !== null && $startsAt > $dueAt) {
            throw new HttpException('时间范围不合法', 409);
        }

        $quoteAmount = $this->decimalFromPost('quote_amount', 0.0);
        $note = $this->optionalString('note');

        $startsAtValue = date('Y-m-d H:i:s', $startsAt);
        $dueAtValue = date('Y-m-d H:i:s', $dueAt);

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare(
                'UPDATE projects SET name = :name, location = :location, status = :status, starts_at = :starts_at,
                        due_at = :due_at, quote_amount = :quote_amount, note = :note
                 WHERE id = :id'
            );
            $stmt->execute([
                ':name' => $name,
                ':location' => $location,
                ':status' => $status,
                ':starts_at' => $startsAtValue,
                ':due_at' => $dueAtValue,
                ':quote_amount' => number_format($quoteAmount, 2, '.', ''),
                ':note' => $note,
                ':id' => $projectId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('项目不存在或无修改', 404);
            }

            AuditLogger::log(
                $actorId,
                'project',
                $projectId,
                'update',
                [
                    'name' => $name,
                    'location' => $location,
                    'starts_at' => $startsAtValue,
                    'due_at' => $dueAtValue,
                    'quote_amount' => $quoteAmount,
                    'status' => $status,
                    'note' => $note,
                ]
            );
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('更新项目失败', 500, $exception);
        }

        return Response::ok();
    }

    public function delete(): string
    {
        $actorId = $this->requireActor();
        if (!$this->actorIsAdmin()) {
            throw new HttpException('未登录或无权限', 403);
        }

        $projectId = $this->requirePositiveInt('project_id');

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $projectId]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('项目不存在', 404);
            }
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('删除项目失败，可能存在关联记录', 500, $exception);
        }

        AuditLogger::log($actorId, 'project', $projectId, 'delete');

        return Response::ok();
    }
}
