<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('studio_recordings')) {
            if (! Schema::hasIndex('studio_recordings', ['recorded_at', 'billable_min'])) {
                Schema::table('studio_recordings', function (Blueprint $table) {
                    $table->index(['recorded_at', 'billable_min'], 'studio_recordings_recorded_billable_idx');
                });
            }
            if (! Schema::hasIndex('studio_recordings', ['recorded_at', 'participant_email'])) {
                Schema::table('studio_recordings', function (Blueprint $table) {
                    $table->index(['recorded_at', 'participant_email'], 'studio_recordings_recorded_email_idx');
                });
            }
        }

        if (Schema::hasTable('payslips') && ! Schema::hasIndex('payslips', ['period'])) {
            Schema::table('payslips', function (Blueprint $table) {
                $table->index('period', 'payslips_period_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('studio_recordings')) {
            if (Schema::hasIndex('studio_recordings', 'studio_recordings_recorded_billable_idx')) {
                Schema::table('studio_recordings', fn (Blueprint $table) => $table->dropIndex('studio_recordings_recorded_billable_idx'));
            }
            if (Schema::hasIndex('studio_recordings', 'studio_recordings_recorded_email_idx')) {
                Schema::table('studio_recordings', fn (Blueprint $table) => $table->dropIndex('studio_recordings_recorded_email_idx'));
            }
        }

        if (Schema::hasTable('payslips') && Schema::hasIndex('payslips', 'payslips_period_idx')) {
            Schema::table('payslips', fn (Blueprint $table) => $table->dropIndex('payslips_period_idx'));
        }
    }
};
