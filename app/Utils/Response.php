<?php

declare(strict_types=1);

namespace App\Utils;

final class Response
{
    public static function ok(): string
    {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');

        return 'OK';
    }

    public static function error(string $reason, int $status): string
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');

        return 'ERROR: ' . $reason;
    }
}
