<?php

namespace App\Handlers;

use App\Http;
use App\Util;

class Assets
{
    public static function index(): void
    {
        Http::json([
            'message' => 'Asset listing endpoint placeholder',
            'received_at' => Util::now(),
        ]);
    }

    public static function store(): void
    {
        Http::json([
            'message' => 'Asset creation endpoint placeholder',
        ], 201);
    }

    public static function show(string $assetId): void
    {
        Http::json([
            'message' => 'Asset detail endpoint placeholder',
            'asset_id' => $assetId,
        ]);
    }

    public static function assign(string $assetId): void
    {
        Http::json([
            'message' => 'Asset assignment endpoint placeholder',
            'asset_id' => $assetId,
        ]);
    }

    public static function release(string $assetId): void
    {
        Http::json([
            'message' => 'Asset return endpoint placeholder',
            'asset_id' => $assetId,
        ]);
    }
}
