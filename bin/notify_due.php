#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Utils\DB;
use App\Utils\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/helpers.php';

Env::load(dirname(__DIR__));

$pdo = DB::connection();

$overdueQuery = $pdo->query(
    'SELECT id, user_id, device_id, due_at
     FROM checkouts
     WHERE return_at IS NULL
       AND due_at < NOW()'
);

$rows = $overdueQuery ? $overdueQuery->fetchAll() : [];
$inserted = 0;

$insertStmt = $pdo->prepare(
    'INSERT INTO notifications (user_id, title, body, not_before, delivered_at, created_at)
     VALUES (:user_id, :title, :body, NULL, NULL, NOW())'
);

foreach ($rows as $row) {
    $userId = (int) ($row['user_id'] ?? 0);
    if ($userId <= 0) {
        continue;
    }

    $checkoutId = (int) $row['id'];
    $deviceId = (int) ($row['device_id'] ?? 0);
    $dueAt = (string) $row['due_at'];

    $body = sprintf(
        '设备 %d 借用记录 #%d 已于 %s 到期且尚未归还，请尽快处理。',
        $deviceId,
        $checkoutId,
        $dueAt
    );

    $insertStmt->execute([
        ':user_id' => $userId,
        ':title' => '借用超期提醒',
        ':body' => $body,
    ]);
    $inserted++;
}

printf("Overdue notifications inserted: %d\n", $inserted);
