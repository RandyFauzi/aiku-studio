<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_recordings', function (Blueprint $table) {
            $table->timestamp('atlas_synced_at')->nullable();
            $table->timestamp('robotics_1618_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('studio_recordings', fn (Blueprint $table) => $table->dropColumn(['atlas_synced_at', 'robotics_1618_synced_at']));
    }
};
