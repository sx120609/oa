<?php

namespace App\Controller;

use App\Domain\Enums;
use App\Domain\ValidationException;
use App\Http\Request;
use App\Infra\Storage;
use App\Middleware\AuthMiddleware;
use App\Util\Helpers;

class AttachmentController
{
    public function upload(Request $request)
    {
        AuthMiddleware::requireRole($request, Enums::ROLES);
        $payload = $request->getJsonBody() ?? [];
        Helpers::requireFields($payload, ['owner_type', 'owner_id', 'filename', 'content']);
        $ownerType = Helpers::stringVal($payload['owner_type'], 'owner_type');
        $ownerId = Helpers::intVal($payload['owner_id'], 'owner_id');
        $filename = Helpers::stringVal($payload['filename'], 'filename');
        $content = base64_decode($payload['content'], true);
        if ($content === false) {
            throw new ValidationException('content must be base64 encoded');
        }
        $path = Storage::saveAttachment($ownerType, $ownerId, $filename, $content);
        return ['path' => $path];
    }
}
