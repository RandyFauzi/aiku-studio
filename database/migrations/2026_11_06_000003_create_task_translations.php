<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_task_translations', function (Blueprint $table) {
            $table->string('normalized_key', 64)->primary();
            $table->string('source_title', 200);
            $table->string('label', 200);
            $table->json('keywords');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_task_translations');
    }
};
