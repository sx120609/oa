<?php

use App\DB;

$app = require __DIR__ . '/../src/bootstrap.php';

$migrationFile = __DIR__ . '/../migrations/001_init.sql';

if (!is_file($migrationFile)) {
    fwrite(STDERR, "Migration file not found: {$migrationFile}\n");
    exit(1);
}

$sql = file_get_contents($migrationFile);

$pdo = DB::pdo();
$pdo->exec('SET NAMES utf8mb4');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql)));

foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
    } catch (\Throwable $throwable) {
        fwrite(STDERR, "Failed to execute statement:\n{$statement}\n{$throwable->getMessage()}\n");
        exit(1);
    }
}

echo "Database initialisation complete using MySQL.\n";
