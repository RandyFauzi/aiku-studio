<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retain evidence even when the report, worker, or recording is later removed.
        Schema::create('recording_owner_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('record_id', 64)->index();
            $table->unsignedBigInteger('contributor_id')->index();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('report_id')->index();
            $table->date('work_date');
            $table->json('evidence');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_owner_confirmations');
    }
};
