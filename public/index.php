<?php
declare(strict_types=1);

$entry = __DIR__ . '/index.html';

if (!is_file($entry)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Frontend entry (public/index.html) not found.';
    return;
}

header('Content-Type: text/html; charset=utf-8');
readfile($entry);
