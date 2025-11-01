<?php

namespace App\Handlers;

use App\Http;

class Reports
{
    public static function summary(): void
    {
        Http::json([
            'message' => 'Reporting endpoint placeholder',
        ]);
    }
}
