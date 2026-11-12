<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->string('type', 32)->default('note');
            $table->string('priority', 32)->default('normal');
            $table->date('note_date')->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('is_completed')->default(false)->index();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('checklist_items')->nullable();
            $table->timestamps();

            $table->index(['note_date', 'is_completed']);
            $table->index(['is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
