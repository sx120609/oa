<?php

namespace App\Handlers;

use App\DB;
use App\Http;
use App\Util;
use PDO;

class Reports
{
    public static function summary(): void
    {
        Http::json([
            'message' => 'Reporting endpoint placeholder',
        ]);
    }

    public static function costs(): void
    {
        $pdo = DB::pdo();

        $sql = <<<SQL
SELECT
    a.id,
    a.name,
    a.model,
    COALESCE(SUM(COALESCE(r.labor_cost, 0) + COALESCE(r.parts_cost, 0)), 0) AS total_cost
FROM assets AS a
LEFT JOIN repair_orders AS r ON r.asset_id = a.id
GROUP BY a.id, a.name, a.model
ORDER BY a.id ASC
SQL;

        $statement = $pdo->query($sql);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['total_cost'] = (float) $row['total_cost'];
        }
        unset($row);

        Http::json([
            'items' => $rows,
            'generated_at' => Util::now(),
        ]);
    }
}
