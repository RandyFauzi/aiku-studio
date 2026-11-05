<?php

// Development-only schema export. Never connects to the operational database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'graphify_schema', 'database.connections.graphify_schema' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
], 'cache.default' => 'array', 'session.driver' => 'array', 'queue.default' => 'sync']);
Illuminate\Support\Facades\DB::purge();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Schema export requires an isolated in-memory database.');
}
$exit = Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'graphify_schema', '--force' => true]);
if ($exit !== 0) {
    throw new RuntimeException(Illuminate\Support\Facades\Artisan::output());
}
$rows = Illuminate\Support\Facades\DB::select("SELECT sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY type DESC, name");
$sql = "-- Generated from Laravel migrations in SQLite :memory:. Structure only; no rows.\n-- SQLite representation, not a MySQL restore script. Logical joins: docs/AI_CONTEXT.md.\n\n";
foreach ($rows as $row) {
    $sql .= $row->sql.";\n\n";
}
$target = base_path('database/schema/ai-structure.sql');
if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0755, true);
}
file_put_contents($target, rtrim($sql)."\n");
echo 'Exported '.count($rows)." schema definitions; no operational rows read.\n";
