<?php

namespace App\Console\Commands;

use App\Models\StudioAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImportLegacyPayroll extends Command
{
    protected $signature = 'payroll:import-legacy {snapshot : Private JSON snapshot} {--files= : Legacy backend/storage directory} {--participants= : Participant mapping JSON} {--atlas-session= : Playwright session JSON}';

    protected $description = 'Import a read-only legacy snapshot into empty Laravel payroll tables';

    public function handle(): int
    {
        $data = json_decode(File::get($this->argument('snapshot')), true, 512, JSON_THROW_ON_ERROR);
        $tables = ['contributors', 'payslips', 'payroll_statuses', 'studio_recordings', 'shift_settings'];
        foreach ($tables as $table) {
            if (! isset($data['tables'][$table]) || ! is_array($data['tables'][$table])) {
                $this->error('Missing table: '.$table);

                return self::FAILURE;
            }
            if (DB::table($table)->exists()) {
                $this->error('Import refused: '.$table.' must be empty.');

                return self::FAILURE;
            }
        }
        if (StudioAccount::exists()) {
            $this->error('Import refused: studio_accounts must be empty.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($data, $tables) {
            foreach ($tables as $table) {
                foreach (array_chunk($data['tables'][$table], 100) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
                $this->info($table.': '.count($data['tables'][$table]).' imported');
            }
            $participants = $this->option('participants') ? json_decode(File::get($this->option('participants')), true, 512, JSON_THROW_ON_ERROR) : [];
            foreach ($data['studio_accounts'] ?? [] as $account) {
                StudioAccount::create(['email' => strtolower($account['email']), 'name' => $account['name'] ?? explode('@', $account['email'])[0],
                    'active' => $account['active'] ?? true, 'created_at' => $account['created_at'] ?? now(),
                    'participant_id' => $participants[strtolower($account['email'])] ?? null]);
            }
        });
        if ($source = $this->option('files')) {
            foreach (['evidence_images', 'pdf_slips'] as $directory) {
                $path = rtrim($source, '/\\').DIRECTORY_SEPARATOR.$directory;
                if (File::isDirectory($path)) {
                    $destination = Storage::disk('local')->path('payroll/'.$directory);
                    File::ensureDirectoryExists($destination);
                    if (! File::copyDirectory($path, $destination)) {
                        throw new \RuntimeException('File copy failed: '.$directory);
                    }
                    $this->info($directory.': '.count(File::allFiles($destination)).' files copied');
                }
            }
        }
        if ($session = $this->option('atlas-session')) {
            $content = File::get($session);
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (! isset($parsed['cookies'])) {
                throw new \RuntimeException('Invalid Atlas session file');
            }
            Storage::disk('local')->put('payroll/atlas-session.json', $content);
            $this->info('Atlas session copied to private storage; credentials were not printed.');
        }
        $this->info('Import finished. Legacy database was not modified.');

        return self::SUCCESS;
    }
}
