<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 11, 7)->nullable()->change();
            $table->double('accuracy_m')->nullable()->change();
            $table->dateTime('gps_captured_at')->nullable()->change();
            $table->string('checkin_method', 20)->default('gps');
            $table->string('client_ip', 45)->nullable();
            $table->boolean('wifi_verified')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['checkin_method', 'client_ip', 'wifi_verified']);
        });
    }
};
