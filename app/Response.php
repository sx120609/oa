<?php

namespace App;

class Response
{
    public static function success($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, ?string $code = null, ?array $details = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $payload = [
            'error' => [
                'message' => $message,
            ],
        ];
        if ($code !== null) {
            $payload['error']['code'] = $code;
        }
        if ($details !== null) {
            $payload['error']['details'] = $details;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
