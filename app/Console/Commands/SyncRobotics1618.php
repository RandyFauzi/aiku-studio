<?php

namespace App\Console\Commands;

use App\Services\Robotics1618Sync;
use Illuminate\Console\Command;

class SyncRobotics1618 extends Command
{
    protected $signature = '1618:sync {--force : Paksa sinkronisasi meskipun sedang berjalan}';

    protected $description = 'Sinkronisasi data rekaman 1618 Robotics khusus untuk akun studio AIKu';

    public function handle(Robotics1618Sync $sync): int
    {
        $this->info('Memulai bot sinkronisasi 1618 Robotics untuk AIKu Studio...');
        $force = (bool) $this->option('force');

        try {
            $result = $sync->sync($force);

            if (($result['status'] ?? '') === 'already_running') {
                $this->warn($result['message']);
                return self::SUCCESS;
            }

            $this->info("✓ " . $result['message']);
            $this->table(
                ['Metrik', 'Jumlah'],
                [
                    ['Total Baris CSV Sumber', number_format($result['total_source_rows'])],
                    ['Akun Studio AIKu Terpantau', number_format($result['monitored_emails_count'])],
                    ['Rekaman Studio Ditemukan & Disimpan', number_format($result['matched_studio_rows'])],
                    ['Waktu Eksekusi', $result['elapsed_seconds'] . ' detik'],
                ]
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Gagal melakukan sinkronisasi 1618: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
