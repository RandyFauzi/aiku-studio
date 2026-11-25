<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contributors', function (Blueprint $table) {
            if (! Schema::hasColumn('contributors', 'badge_color')) {
                $table->string('badge_color', 32)->nullable()->after('role');
            }
        });

        // Isi warna bawaan untuk kontributor lama yang sebelumnya di-hardcode di UI.
        $defaults = [
            'aufa' => 'violet',
            'zikri' => 'blue',
            'ghina' => 'rose',
            'rio' => 'cyan',
            'rahmat' => 'emerald',
            'yoga' => 'amber',
            'diyoga' => 'amber',
            'tina' => 'purple',
            'tilopha' => 'purple',
            'fahri' => 'indigo',
            'aida' => 'pink',
            'maryam' => 'teal',
            'mariyam' => 'teal',
        ];

        $rows = DB::table('contributors')->select('id', 'nama', 'badge_color')->get();
        foreach ($rows as $row) {
            if (! empty($row->badge_color)) {
                continue;
            }
            $lower = mb_strtolower((string) $row->nama);
            foreach ($defaults as $keyword => $color) {
                if (str_contains($lower, $keyword)) {
                    DB::table('contributors')->where('id', $row->id)->update(['badge_color' => $color]);
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('contributors', function (Blueprint $table) {
            if (Schema::hasColumn('contributors', 'badge_color')) {
                $table->dropColumn('badge_color');
            }
        });
    }
};
