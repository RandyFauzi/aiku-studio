<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Serialize all payroll mutations for one worker, including cross-period claims.
        Schema::create('payroll_write_locks', function (Blueprint $table) {
            $table->string('worker_key', 64)->primary();
        });
        Schema::table('payslips', function (Blueprint $table) {
            // Legacy duplicates stay intact until an administrator chooses a primary slip.
            $table->string('active_key', 64)->nullable()->unique();
            $table->foreignId('superseded_by_id')->nullable()->constrained('payslips')->restrictOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->unsignedBigInteger('superseded_by_user_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropForeign(['superseded_by_id']);
            $table->dropUnique(['active_key']);
            $table->dropColumn(['active_key', 'superseded_by_id', 'superseded_at', 'superseded_by_user_id']);
        });
        Schema::dropIfExists('payroll_write_locks');
    }
};
