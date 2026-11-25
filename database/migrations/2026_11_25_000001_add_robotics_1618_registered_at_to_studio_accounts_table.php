<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('studio_accounts', 'robotics_1618_registered_at')) {
                $table->timestamp('robotics_1618_registered_at')->nullable()->after('participant_id');
            }
        });

        $now = now();

        // Tandai seluruh akun studio yang sudah ada saat ini sebagai sudah terdaftar di 1618.
        DB::table('studio_accounts')
            ->whereNull('robotics_1618_registered_at')
            ->update(['robotics_1618_registered_at' => $now]);

        // Pastikan seluruh email yang sudah ada di device_accounts juga tercatat sebagai sudah terdaftar.
        if (Schema::hasTable('device_accounts')) {
            $deviceRows = DB::table('device_accounts')
                ->select('email', 'account_name', 'phone_key')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->get();

            foreach ($deviceRows as $row) {
                $email = strtolower(trim((string) $row->email));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $exists = DB::table('studio_accounts')->where('email', $email)->exists();
                if (! $exists) {
                    DB::table('studio_accounts')->insert([
                        'email' => $email,
                        'name' => trim((string) ($row->account_name ?? '')) ?: explode('@', $email)[0],
                        'active' => true,
                        'robotics_1618_registered_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('studio_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('studio_accounts', 'robotics_1618_registered_at')) {
                $table->dropColumn('robotics_1618_registered_at');
            }
        });
    }
};
