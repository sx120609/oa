<?php

namespace App\Infra;

class Storage
{
    private static string $basePath = __DIR__ . '/../../storage';

    public static function saveAttachment(string $ownerType, int $ownerId, string $filename, string $contents): string
    {
        $dir = self::$basePath . '/attachments/' . $ownerType . '/' . $ownerId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $path = $dir . '/' . uniqid('file_', true) . '_' . $safeName;
        file_put_contents($path, $contents);

        return $path;
    }
}
