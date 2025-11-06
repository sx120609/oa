<?php

declare(strict_types=1);

namespace App\Controllers;

use function csrf_field;
use function view;

final class HomeController extends Controller
{
    public function index(): string
    {
        return view('home', [
            'session' => [
                'uid' => $_SESSION['uid'] ?? null,
                'role' => $_SESSION['role'] ?? null,
                'email' => $_SESSION['user_email'] ?? null,
            ],
        ]);
    }
}
