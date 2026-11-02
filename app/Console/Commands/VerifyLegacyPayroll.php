<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class VerifyLegacyPayroll extends Command
{
    protected $signature = 'payroll:verify-legacy {snapshot} {--files= : Original backend/storage folder}';

    protected $description = 'Compare every imported field and file checksum to the legacy snapshot';

    public function handle(): int
    {
        $data = json_decode(File::get($this->argument('snapshot')), true, 512, JSON_THROW_ON_ERROR);
        $errors = [];
        $counts = [];
        $fields = 0;
        foreach ($data['tables'] as $table => $rows) {
            if (! in_array($table, ['contributors', 'payslips', 'payroll_statuses', 'studio_recordings', 'shift_settings'])) {
                throw new \RuntimeException('Unexpected table');
            }
            $key = $table === 'studio_recordings' ? 'record_id' : 'id';
            $actual = DB::table($table)->get()->keyBy($key);
            $counts[$table] = ['source' => count($rows), 'target' => $actual->count()];
            if (count($rows) !== $actual->count()) {
                $errors[] = $table.': count mismatch';
            }
            foreach ($rows as $row) {
                $target = (array) $actual->get($row[$key]);
                foreach ($row as $field => $value) {
                    $fields++;
                    if (! $this->equivalent($value, $target[$field] ?? null)) {
                        $errors[] = $table.' row '.$row[$key].' field '.$field;
                    }
                }
            }
        }
        $filesCount = 0;
        if ($source = $this->option('files')) {
            foreach (['evidence_images', 'pdf_slips'] as $folder) {
                $base = rtrim($source, '/\\').DIRECTORY_SEPARATOR.$folder;
                foreach (File::allFiles($base) as $file) {
                    $target = Storage::disk('local')->path('payroll/'.$folder.'/'.$file->getRelativePathname());
                    if (! is_file($target) || hash_file('sha256', $file->getPathname()) !== hash_file('sha256', $target)) {
                        $errors[] = 'File checksum mismatch: '.$folder.'/'.$file->getRelativePathname();
                    }
                    $filesCount++;
                }
            }
        }
        $report = ['checked_at' => now()->toIso8601String(), 'database_driver' => DB::getDriverName(), 'tables' => $counts,
            'fields_checked' => $fields, 'files_checked' => $filesCount, 'errors' => $errors];
        Storage::disk('local')->put('migration/verification.json', json_encode($report, JSON_PRETTY_PRINT));
        foreach ($counts as $table => $count) {
            $this->line($table.': '.$count['source'].' / '.$count['target']);
        }
        $this->line($fields.' fields and '.$filesCount.' file hashes checked.');
        if ($errors) {
            foreach (array_slice($errors, 0, 20) as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        $this->info('All imported values and files match.');

        return self::SUCCESS;
    }

    private function equivalent(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.00001;
        }
        if (is_string($a) && is_string($b)) {
            $ja = json_decode($a, true);
            $jb = json_decode($b, true);
            if (is_array($ja) && is_array($jb)) {
                return $ja == $jb;
            }
        }

        return (string) $a === (string) $b;
    }
}
