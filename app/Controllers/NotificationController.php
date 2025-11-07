<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDOException;

final class NotificationController extends Controller
{
    public function delete(): string
    {
        $actorId = $this->requireActor();
        if (!$this->actorIsAdmin()) {
            throw new HttpException('未登录或无权限', 403);
        }

        $notificationId = $this->requirePositiveInt('notification_id');

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $notificationId]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('通知不存在', 404);
            }
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('删除通知失败', 500, $exception);
        }

        return Response::ok();
    }
}
