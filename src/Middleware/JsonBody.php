<?php

namespace App\Middleware;

use App\Domain\ValidationException;
use App\Http\Request;

class JsonBody
{
    public static function parse(Request $request): void
    {
        $contentType = $request->getHeader('Content-Type', '');
        if ($request->getMethod() === 'GET') {
            return;
        }
        if (stripos($contentType, 'application/json') === false) {
            $request->setJsonBody(null);
            return;
        }
        $raw = $request->getRawBody();
        if ($raw === null || $raw === '') {
            $request->setJsonBody([]);
            return;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('Invalid JSON body');
        }
        $request->setJsonBody($decoded);
    }
}
