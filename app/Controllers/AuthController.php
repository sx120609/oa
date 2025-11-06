<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDO;

final class AuthController extends Controller
{
    public function login(): string
    {
        $email = strtolower($this->requireString('email'));
        $password = $this->requireString('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('账号或密码错误', 401);
        }

        $user = $this->findUserByEmail($email);
        if ($user === null) {
            throw new HttpException('账号或密码错误', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new HttpException('账号或密码错误', 401);
        }

        session_regenerate_id(true);

        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_email'] = $email;

        AuditLogger::log(
            (int) $user['id'],
            'user',
            (int) $user['id'],
            'login',
            [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'n/a', 0, 255),
            ]
        );

        return Response::ok();
    }

    private function findUserByEmail(string $email): ?array
    {
        $pdo = DB::connection();
        $stmt = $pdo->prepare(
            'SELECT id, password_hash, role FROM users WHERE LOWER(email) = :email LIMIT 1'
        );

        $stmt->execute([':email' => $email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }

        return [
            'id' => (int) $result['id'],
            'password_hash' => (string) $result['password_hash'],
            'role' => (string) $result['role'],
        ];
    }
}
