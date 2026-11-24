<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_tool_calls', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 36)->index();
            $table->uuid('mcp_connection_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('role', 32)->nullable();
            $table->string('method', 64);
            $table->string('tool_name', 100)->nullable()->index();
            $table->text('input_digest')->nullable();
            $table->string('outcome', 32);
            $table->unsignedSmallInteger('http_status')->default(200);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_tool_calls');
    }
};
