<?php

// CLI only: read the legacy database under a consistent snapshot, never alter it.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__);
$source = $argv[1] ?? throw new RuntimeException('Legacy project path required');
$db = new PDO(getenv('LEGACY_DSN') ?: 'mysql:host=127.0.0.1;dbname=aiku_payroll;charset=utf8mb4', getenv('LEGACY_DB_USER') ?: 'root', getenv('LEGACY_DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('SET TRANSACTION READ ONLY');
$db->beginTransaction();
$data = ['exported_at' => gmdate('c'), 'tables' => []];
foreach (['contributors', 'payslips', 'payroll_statuses', 'studio_recordings', 'shift_settings'] as $table) {
    $rows = $db->query('SELECT * FROM `'.$table.'` ORDER BY '.($table === 'studio_recordings' ? 'record_id' : 'id'))->fetchAll(PDO::FETCH_ASSOC);
    $data['tables'][$table] = $rows;
    echo $table.': '.count($rows).PHP_EOL;
}
$db->commit();
$accountsPath = $source.'/backend/storage/studio_accounts.json';
$data['studio_accounts'] = is_file($accountsPath) ? json_decode(file_get_contents($accountsPath), true, 512, JSON_THROW_ON_ERROR) : [];
$directory = $root.'/storage/app/private/migration';
if (! is_dir($directory)) {
    mkdir($directory, 0700, true);
}
$path = $directory.'/legacy-snapshot.json';
if (file_exists($path)) {
    throw new RuntimeException('Snapshot already exists; choose a new name instead of overwriting it.');
}
file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Private snapshot saved. SHA256: '.hash_file('sha256', $path).PHP_EOL;
