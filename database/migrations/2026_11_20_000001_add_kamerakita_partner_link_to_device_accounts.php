<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_accounts', function (Blueprint $table) {
            $table->uuid('kamerakita_partner_id')->nullable()->index();
            $table->string('kamerakita_partner_code', 40)->nullable();
            $table->string('kamerakita_status', 24)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('device_accounts', function (Blueprint $table) {
            $table->dropIndex(['kamerakita_partner_id']);
            $table->dropColumn(['kamerakita_partner_id', 'kamerakita_partner_code', 'kamerakita_status']);
        });
    }
};
