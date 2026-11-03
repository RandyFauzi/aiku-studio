<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_key', 32)->index();
            $table->string('email', 128)->index();
            $table->string('account_name', 128)->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['phone_key', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_accounts');
    }
};
