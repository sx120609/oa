<?php

namespace App\Middleware;

use App\Domain\AuthorizationException;
use App\Infra\Logger;
use App\Repository\UserRepo;
use App\Http\Request;

class AuthMiddleware
{
    private static string $appKey = 'demo-key';
    private static ?UserRepo $userRepo = null;

    public static function configure(string $appKey, UserRepo $userRepo): void
    {
        self::$appKey = $appKey;
        self::$userRepo = $userRepo;
    }

    public static function authenticate(Request $request): void
    {
        $header = $request->getHeader('Authorization');
        if (!$header || stripos($header, 'Bearer ') !== 0) {
            return;
        }
        $token = trim(substr($header, 7));
        $payload = self::decodeToken($token);
        if (!$payload) {
            return;
        }
        $user = self::$userRepo ? self::$userRepo->findById((int)($payload['sub'] ?? 0)) : null;
        if ($user) {
            $request->setUser($user);
        }
    }

    public static function requireRole(Request $request, array $roles): void
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required');
        }
        if (!in_array($user['role'], $roles, true)) {
            throw new AuthorizationException('Permission denied');
        }
    }

    public static function generateToken(int $userId): string
    {
        $payload = ['sub' => $userId, 'iat' => time()];
        $json = json_encode($payload);
        $signature = hash_hmac('sha256', $json, self::$appKey);
        return base64_encode($json . '.' . $signature);
    }

    private static function decodeToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);
        if (!$decoded) {
            return null;
        }
        [$json, $sig] = explode('.', $decoded, 2) + [null, null];
        if (!$json || !$sig) {
            return null;
        }
        $expected = hash_hmac('sha256', $json, self::$appKey);
        if (!hash_equals($expected, $sig)) {
            Logger::error('Invalid token signature');
            return null;
        }
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return null;
        }
        return $payload;
    }
}
