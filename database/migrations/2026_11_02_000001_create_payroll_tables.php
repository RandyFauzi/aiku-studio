<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributors', function (Blueprint $table) {
            $table->id();
            foreach (['nama', 'no_wa', 'domisili', 'no_rek', 'shift', 'hp', 'email'] as $field) {
                $table->string($field)->nullable();
            }
            $table->index('nama');
            $table->index('email');
        });
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->string('employee_name');
            $table->string('period');
            $table->double('total_logged_hours')->default(0);
            $table->double('total_approved_hours')->default(0);
            $table->bigInteger('total_thp')->default(0);
            $table->bigInteger('bonus')->default(0);
            $table->enum('status', ['belum', 'sudah'])->default('belum');
            $table->string('file_path')->nullable();
            $table->json('evidence_data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['employee_name', 'period']);
        });
        Schema::create('payroll_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('worker_name');
            $table->string('period');
            $table->enum('status', ['belum', 'sudah'])->default('belum');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['worker_name', 'period']);
        });
        Schema::create('studio_recordings', function (Blueprint $table) {
            $table->string('record_id', 64)->primary();
            $table->string('participant_email', 128)->index();
            $table->string('task_title')->nullable();
            $table->string('recorded_utc', 64)->nullable();
            $table->dateTime('recorded_at')->nullable()->index();
            foreach (['duration_sec', 'duration_min', 'billable_min', 'rejected_min', 'pending_min', 'deferred_min'] as $field) {
                $table->double($field)->default(0);
            }
            $table->string('processing_status', 64)->nullable();
            $table->string('billable_status', 32)->nullable();
            $table->string('reviewed_utc', 64)->nullable();
            $table->string('reconciliation', 64)->nullable();
            // Keep historical assignments if a contributor is removed.
            $table->unsignedBigInteger('assigned_contributor_id')->nullable()->index();
            $table->string('assigned_worker_name', 100)->nullable()->index();
            $table->string('manual_payment_status', 32)->default('Belum Rekap')->index();
            $table->text('notes')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->dateTime('createdAt')->nullable();
            $table->dateTime('updatedAt')->nullable();
        });
        Schema::create('shift_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 64)->unique();
            $table->json('setting_value');
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('studio_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 128)->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->string('participant_id', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['studio_accounts', 'shift_settings', 'studio_recordings', 'payroll_statuses', 'payslips', 'contributors'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
