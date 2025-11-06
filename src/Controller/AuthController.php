<?php

namespace App\Controller;

use App\Domain\ValidationException;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Repository\UserRepo;
use App\Util\Helpers;

class AuthController
{
    private UserRepo $users;

    public function __construct(UserRepo $users)
    {
        $this->users = $users;
    }

    public function login(Request $request)
    {
        $payload = $request->getJsonBody() ?? [];
        Helpers::requireFields($payload, ['username', 'password']);
        $username = Helpers::stringVal($payload['username'], 'username');
        $password = Helpers::stringVal($payload['password'], 'password');
        $user = $this->users->findByUsername($username);
        $valid = false;
        if ($user) {
            if (password_get_info($user['password_hash'])['algo'] !== 0) {
                $valid = password_verify($password, $user['password_hash']);
            } else {
                $valid = hash_equals($user['password_hash'], $password);
            }
        }
        if (!$user || !$valid) {
            throw new ValidationException('Invalid credentials');
        }
        $token = AuthMiddleware::generateToken((int)$user['id']);
        return ['token' => $token, 'user' => ['id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role']]];
    }
}
