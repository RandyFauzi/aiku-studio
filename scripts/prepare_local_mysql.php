<?php

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$db = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'aiku_studio_laravel';
$exists = $db->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'aiku_studio_laravel'")->fetchColumn();
if ($exists) {
    throw new RuntimeException('Target database already exists. Refusing to overwrite.');
}
$db->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$path = dirname(__DIR__).'/.env';
$content = file_get_contents($path);
copy($path, dirname(__DIR__).'/storage/app/private/migration/env-sqlite-backup.txt');
$content = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=mysql', $content);
foreach (['DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_DATABASE' => $name, 'DB_USERNAME' => 'root', 'DB_PASSWORD' => ''] as $key => $value) {
    $content = preg_replace('/^#?\s*'.preg_quote($key, '/').'=.*/m', $key.'='.$value, $content);
}
file_put_contents($path, $content);
echo 'Created isolated database '.$name.'. Source aiku_payroll was not changed.'.PHP_EOL;
