<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->bigInteger('pending_carry_over_amount')->default(0)->after('bonus');
            $table->double('pending_carry_over_hours')->default(0)->after('pending_carry_over_amount');
            $table->json('pending_carry_over_data')->nullable()->after('pending_carry_over_hours');
            $table->boolean('is_locked')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['pending_carry_over_amount', 'pending_carry_over_hours', 'pending_carry_over_data', 'is_locked']);
        });
    }
};
