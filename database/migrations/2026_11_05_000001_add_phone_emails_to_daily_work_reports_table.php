<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_work_reports', 'phone_emails')) {
                $table->json('phone_emails')->nullable()->after('phone_durations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_work_reports', 'phone_emails')) {
                $table->dropColumn('phone_emails');
            }
        });
    }
};
