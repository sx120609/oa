<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDO;
use PDOException;

final class UserController extends Controller
{
    private const ROLES = ['owner', 'asset_admin', 'planner', 'photographer'];

    public function create(): string
    {
        $actorId = $this->requireActor();

        $name = $this->requireString('name');
        $email = strtolower($this->requireString('email'));
        $password = $this->requireString('password');
        $role = strtolower($this->requireString('role'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('邮箱格式不正确', 409);
        }

        if (!in_array($role, self::ROLES, true)) {
            throw new HttpException('角色类型不合法', 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw new HttpException('密码加密失败', 500);
        }

        try {
            $pdo = DB::connection();

            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);
            if ($check->fetchColumn()) {
                throw new HttpException('邮箱已存在', 409);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO users (email, name, password_hash, role, created_at)
                 VALUES (:email, :name, :password_hash, :role, NOW())'
            );
            $stmt->execute([
                ':email' => $email,
                ':name' => $name,
                ':password_hash' => $hash,
                ':role' => $role,
            ]);

            $userId = (int) $pdo->lastInsertId();

            AuditLogger::log($actorId, 'user', $userId, 'create', [
                'email' => $email,
                'name' => $name,
                'role' => $role,
            ]);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('用户创建失败', 500, $exception);
        }

        return Response::ok();
    }

    public function delete(): string
    {
        $actorId = $this->requireActor();
        $userId = $this->requirePositiveInt('user_id');

        if ($userId === $actorId) {
            throw new HttpException('不能删除当前登录用户', 409);
        }

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('用户不存在', 404);
            }

            AuditLogger::log($actorId, 'user', $userId, 'delete');
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('删除用户失败', 500, $exception);
        }

        return Response::ok();
    }
}
