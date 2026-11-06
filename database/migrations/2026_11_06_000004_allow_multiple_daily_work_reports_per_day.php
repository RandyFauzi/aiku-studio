<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL commits DDL independently; retries must tolerate partial deploys.
        foreach (Schema::getIndexes('daily_work_reports') as $index) {
            if ($index['unique'] && $index['columns'] === ['user_id', 'work_date']) {
                Schema::table('daily_work_reports', fn (Blueprint $table) => $table->dropUnique($index['name']));
            }
        }
        if (! Schema::hasIndex('daily_work_reports', ['user_id', 'work_date'])) {
            Schema::table('daily_work_reports', fn (Blueprint $table) => $table->index(['user_id', 'work_date']));
        }

        Schema::table('daily_work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_work_reports', 'shift_label')) {
                $table->string('shift_label', 50)->nullable()->after('work_date');
            }
            if (!Schema::hasColumn('daily_work_reports', 'notes')) {
                $table->string('notes', 255)->nullable()->after('phone_tasks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_work_reports', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('daily_work_reports', 'shift_label')) {
                $table->dropColumn('shift_label');
            }
            $table->dropIndex(['user_id', 'work_date']);
            $table->unique(['user_id', 'work_date']);
        });
    }
};
