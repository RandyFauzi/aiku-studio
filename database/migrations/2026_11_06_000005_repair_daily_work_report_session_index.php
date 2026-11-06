<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Repair installations where the legacy index survived an earlier deploy.
        $migration = require __DIR__.'/2026_11_06_000004_allow_multiple_daily_work_reports_per_day.php';
        $migration->up();
    }

    public function down(): void
    {
        // Do not restore uniqueness over existing multi-session reports.
    }
};
