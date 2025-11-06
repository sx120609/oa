<?php

declare(strict_types=1);

namespace App\Services;

use App\Utils\DB;
use App\Utils\HttpException;
use JsonException;
use PDOException;

final class AuditLogger
{
    public static function log(
        ?int $actorId,
        string $entityType,
        int $entityId,
        string $action,
        array $detail = []
    ): void {
        try {
            $json = json_encode($detail, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new HttpException('审计日志编码失败', 500, $e);
        }

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs (actor_id, entity_type, entity_id, action, detail, created_at)
                 VALUES (:actor_id, :entity_type, :entity_id, :action, :detail, NOW())'
            );

            $stmt->execute([
                ':actor_id' => $actorId,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':action' => $action,
                ':detail' => $json,
            ]);
        } catch (PDOException $e) {
            error_log(sprintf('Audit log insert failed: %s', $e->getMessage()));
            // 不影响主流程，记录错误即可
            return;
        }
    }
}
