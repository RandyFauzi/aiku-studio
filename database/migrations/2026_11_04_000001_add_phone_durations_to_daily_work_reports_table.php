<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_work_reports', 'phone_durations')) {
                $table->json('phone_durations')->nullable()->after('phones');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_work_reports', 'phone_durations')) {
                $table->dropColumn('phone_durations');
            }
        });
    }
};
