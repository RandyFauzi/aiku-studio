<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'attendance_status')) {
                $table->string('attendance_status', 32)->default('hadir')->after('checkin_method');
            }
            if (! Schema::hasColumn('attendances', 'notes')) {
                $table->string('notes', 255)->nullable()->after('client_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['attendance_status', 'notes'],
                fn ($c) => Schema::hasColumn('attendances', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
