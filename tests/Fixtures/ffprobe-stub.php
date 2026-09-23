#!/usr/bin/env php
<?php

declare(strict_types=1);

$arguments = $_SERVER['argv'] ?? [];
$last = $arguments === [] ? '' : $arguments[array_key_last($arguments)];
$path = is_string($last) ? $last : '';
$basename = basename($path);

if (str_contains($basename, 'ffprobe-stub-sleep')) {
    sleep(2);
    echo '{"ok":true}';
    exit(0);
}

if (str_contains($basename, 'ffprobe-stub-empty')) {
    fwrite(STDERR, 'warning only');
    exit(0);
}

if (str_contains($basename, 'ffprobe-stub-blank')) {
    echo " \n\t";
    exit(0);
}

if (str_contains($basename, 'ffprobe-stub-fail')) {
    fwrite(STDERR, 'ffprobe stub failed');
    exit(7);
}

if (str_contains($basename, 'ffprobe-stub-args')) {
    echo json_encode(['argv' => array_values($arguments)], JSON_THROW_ON_ERROR);
    exit(0);
}

echo "{\"ok\":true}\n";
exit(0);
