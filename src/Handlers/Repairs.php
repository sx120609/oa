<?php

namespace App\Handlers;

use App\Http;

class Repairs
{
    public static function index(): void
    {
        Http::json([
            'message' => 'Repair order listing endpoint placeholder',
        ]);
    }

    public static function store(string $assetId): void
    {
        Http::json([
            'message' => 'Repair order creation endpoint placeholder',
            'asset_id' => $assetId,
        ], 201);
    }

    public static function updateStatus(string $orderId): void
    {
        Http::json([
            'message' => 'Repair order status update endpoint placeholder',
            'order_id' => $orderId,
        ]);
    }
}
