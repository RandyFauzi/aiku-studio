<?php

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$db = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'aiku_studio_restore_check';
if ($db->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'aiku_studio_restore_check'")->fetchColumn()) {
    throw new RuntimeException('Restore-check database already exists; refusing to overwrite.');
}
$db->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    $db->exec('USE `'.$name.'`');
    $db->exec(file_get_contents(dirname(__DIR__).'/storage/app/private/migration/aiku-studio-hostinger.sql'));
    foreach (['contributors' => 16, 'payslips' => 16, 'payroll_statuses' => 16, 'studio_recordings' => 2296, 'studio_accounts' => 38, 'sessions' => 0, 'cache' => 0, 'cache_locks' => 0] as $table => $expected) {
        $actual = (int) $db->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
        if ($actual !== $expected) {
            throw new RuntimeException('Restore count mismatch: '.$table);
        }
        echo $table.': '.$actual.' verified'.PHP_EOL;
    }
    echo 'MySQL dump restores successfully without local login sessions/cache.'.PHP_EOL;
} finally {
    // This exact disposable database was created by this script after a nonexistence check.
    $db->exec('DROP DATABASE `aiku_studio_restore_check`');
}
