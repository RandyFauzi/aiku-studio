<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_devices', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label', 60);
            $table->timestamps();
        });
        foreach (['putih' => 'Putih', 'pink' => 'Pink', 'biru' => 'Biru', 'hijau' => 'Hijau', 'hitam' => 'Hitam', 'pink_st' => 'Pink ST'] as $key => $label) {
            DB::table('studio_devices')->insert(['key' => $key, 'label' => $label, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_devices');
    }
};
