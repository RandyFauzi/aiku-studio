<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('investors')) {
            Schema::create('investors', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name', 120);
                $table->string('no_wa', 40)->nullable();
                $table->string('email', 128)->nullable();
                $table->string('bank_name', 60)->nullable();
                $table->string('no_rek', 80)->nullable();
                $table->string('account_holder', 120)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('investor_device_contracts')) {
            Schema::create('investor_device_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
                $table->string('device_key', 64)->index();
                $table->string('device_code', 64)->unique();
                $table->unsignedInteger('rate_per_hour_idr')->default(10000);
                $table->string('rate_basis', 24)->default('billable');
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('notes', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('investor_payouts')) {
            Schema::create('investor_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
                $table->string('period_id', 64);
                $table->string('period_name', 120);
                $table->decimal('total_billable_hours', 10, 2)->default(0);
                $table->unsignedBigInteger('total_amount_idr')->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['investor_id', 'period_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_payouts');
        Schema::dropIfExists('investor_device_contracts');
        Schema::dropIfExists('investors');
    }
};
