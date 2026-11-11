<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_recordings', function (Blueprint $table) {
            $table->decimal('prev_billable_min', 8, 2)->nullable()->after('billable_min');
            $table->decimal('prev_rejected_min', 8, 2)->nullable()->after('rejected_min');
            $table->timestamp('qc_changed_at')->nullable()->after('last_synced_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('studio_recordings', function (Blueprint $table) {
            $table->dropIndex(['qc_changed_at']);
            $table->dropColumn(['prev_billable_min', 'prev_rejected_min', 'qc_changed_at']);
        });
    }
};
