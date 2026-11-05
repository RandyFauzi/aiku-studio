<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_streak_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contributor_id')->index();
            $table->date('work_date');
            $table->json('schedule_snapshot');
            $table->dateTime('deadline_at');
            $table->unsignedSmallInteger('target_minutes');
            $table->unsignedSmallInteger('rule_version')->default(1);
            $table->string('status', 20)->default('open');
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->decimal('verified_minutes', 12, 2)->default(0);
            $table->json('evidence_ids')->nullable();
            $table->unsignedBigInteger('manual_report_id')->nullable();
            $table->unsignedInteger('manual_report_revision')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('celebrated_at')->nullable();
            $table->timestamps();
            $table->unique(['contributor_id', 'work_date']);
        });
        // Start when the feature is installed, without penalising pre-feature history.
        DB::table('shift_settings')->insertOrIgnore([
            'setting_key' => 'record_streak_policy',
            'setting_value' => json_encode(['start_date' => now('Asia/Makassar')->toDateString()]),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('record_streak_days');
        DB::table('shift_settings')->where('setting_key', 'record_streak_policy')->delete();
    }
};
