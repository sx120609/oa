<?php

namespace App\Http;

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $message, int $status = 400, array $details = []): void
    {
        $payload = ['code' => $status, 'message' => $message];
        if (!empty($details)) {
            $payload['details'] = $details;
        }
        self::json($payload, $status);
    }
}
