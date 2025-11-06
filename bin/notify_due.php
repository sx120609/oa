#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Utils\DB;
use App\Utils\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/helpers.php';

Env::load(dirname(__DIR__));

$pdo = DB::connection();

$query = $pdo->query(
    'SELECT id, user_id, device_id, project_id, due_at,
            CASE
                WHEN TIMESTAMPDIFF(HOUR, NOW(), due_at) BETWEEN 20 AND 24 THEN "24h"
                WHEN TIMESTAMPDIFF(MINUTE, NOW(), due_at) BETWEEN 0 AND 120 THEN "2h"
            END AS window
     FROM checkouts
     WHERE return_at IS NULL
       AND (
           TIMESTAMPDIFF(HOUR, NOW(), due_at) BETWEEN 20 AND 24
           OR TIMESTAMPDIFF(MINUTE, NOW(), due_at) BETWEEN 0 AND 120
       )'
);
$rows = [];
if ($query !== false) {
    $rows = $query->fetchAll();
}
$inserted = 0;

$checkStmt = $pdo->prepare(
    'SELECT id FROM notifications
     WHERE user_id = :user_id AND title = :title AND body = :body
     LIMIT 1'
);

$insertStmt = $pdo->prepare(
    'INSERT INTO notifications (user_id, title, body, not_before, delivered_at, created_at)
     VALUES (:user_id, :title, :body, NULL, NULL, NOW())'
);

foreach ($rows as $row) {
    $window = $row['window'] ?? null;
    if ($window === null) {
        continue;
    }

    $checkoutId = (int) $row['id'];
    $userId = (int) $row['user_id'];
    if ($userId <= 0) {
        continue;
    }

    $dueAt = (string) $row['due_at'];
    $deviceId = (int) ($row['device_id'] ?? 0);

    $title = '到期提醒';
    $body = sprintf(
        'Checkout #%d for device %d is due at %s (%s window).',
        $checkoutId,
        $deviceId,
        $dueAt,
        $window
    );

    $checkStmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':body' => $body,
    ]);

    if ($checkStmt->fetchColumn()) {
        $checkStmt->closeCursor();
        continue;
    }
    $checkStmt->closeCursor();

    $insertStmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':body' => $body,
    ]);
    $insertStmt->closeCursor();

    $inserted++;
}

printf("Notifications inserted: %d\n", $inserted);
