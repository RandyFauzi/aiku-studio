<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_work_reports', fn (Blueprint $table) => $table->json('phone_tasks')->nullable());
        Schema::create('report_task_options', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('normalized_key', 64)->unique();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_task_options');
        Schema::table('daily_work_reports', fn (Blueprint $table) => $table->dropColumn('phone_tasks'));
    }
};
