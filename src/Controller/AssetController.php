<?php

namespace App\Controller;

use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Domain\Enums;

class AssetController
{
    public function qrcode(Request $request, int $id)
    {
        AuthMiddleware::requireRole($request, Enums::ROLES);
        $url = sprintf('https://demo.example/assets/%d', $id);
        return ['asset_id' => $id, 'qrcode_url' => $url];
    }
}
