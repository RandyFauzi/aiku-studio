<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'checkin_method')) {
                $table->string('checkin_method', 20)->default('gps');
            }
            if (!Schema::hasColumn('attendances', 'client_ip')) {
                $table->string('client_ip', 45)->nullable();
            }
            if (!Schema::hasColumn('attendances', 'wifi_verified')) {
                $table->boolean('wifi_verified')->default(false);
            }
        });

        // Make GPS columns nullable safely using raw SQL if MySQL
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `attendances` MODIFY `latitude` DECIMAL(10, 7) NULL, MODIFY `longitude` DECIMAL(11, 7) NULL, MODIFY `accuracy_m` DOUBLE NULL, MODIFY `gps_captured_at` DATETIME NULL');
            } else {
                Schema::table('attendances', function (Blueprint $table) {
                    $table->decimal('latitude', 10, 7)->nullable()->change();
                    $table->decimal('longitude', 11, 7)->nullable()->change();
                    $table->double('accuracy_m')->nullable()->change();
                    $table->dateTime('gps_captured_at')->nullable()->change();
                });
            }
        } catch (\Throwable $e) {
            // Safe fallback if already modified or driver not supporting
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $cols = array_filter(['checkin_method', 'client_ip', 'wifi_verified'], fn ($c) => Schema::hasColumn('attendances', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
