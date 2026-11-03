<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('contributor');
            $table->unsignedBigInteger('contributor_id')->nullable()->unique();
            $table->boolean('is_active')->default(true);
        });
        // All accounts that existed before contributor access were administrators.
        DB::table('users')->update(['role' => 'admin']);
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('contributor_id')->index();
            $table->string('contributor_name');
            $table->date('work_date')->index();
            $table->dateTime('checked_in_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 11, 7);
            $table->double('accuracy_m');
            $table->dateTime('gps_captured_at');
            $table->timestamps();
            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['contributor_id']);
            $table->dropColumn(['role', 'contributor_id', 'is_active']);
        });
    }
};
