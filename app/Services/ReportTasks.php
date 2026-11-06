<?php

namespace App\Services;

use App\Models\StudioRecording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReportTasks
{
    public static function title(string $title): string
    {
        return preg_replace('/\s+/u', ' ', trim($title));
    }

    public static function key(string $title): string
    {
        return hash('sha256', mb_strtolower(self::title($title)));
    }

    public function forReport(array $fields, array $phones, array $existing = []): array
    {
        if (! array_key_exists('phone_tasks', $fields)) {
            return array_intersect_key($existing, array_flip($phones));
        }
        $tasks = $fields['phone_tasks'];
        if (array_diff(array_keys($tasks), $phones)) {
            throw ValidationException::withMessages(['phone_tasks' => 'Task hanya boleh diisi untuk HP yang dipilih.']);
        }
        $result = [];
        foreach ($phones as $phone) {
            $titles = [];
            foreach ($tasks[$phone] ?? [] as $title) {
                $title = self::title($title);
                if ($title === '' || preg_match('/[\p{C}]/u', $title)) {
                    throw ValidationException::withMessages(['phone_tasks.'.$phone => 'Nama task tidak valid.']);
                }
                $titles[self::key($title)] ??= $title;
            }
            if (! $titles) {
                throw ValidationException::withMessages(['phone_tasks.'.$phone => 'Pilih minimal satu task untuk setiap HP yang dipakai.']);
            }
            $result[$phone] = array_values($titles);
        }
        return $result;
    }

    public function remember(array $phoneTasks): void
    {
        $rows = [];
        foreach ($phoneTasks as $titles) {
            foreach ($titles as $title) {
                $key = self::key($title);
                $rows[$key] = ['title' => $title, 'normalized_key' => $key, 'created_at' => now()];
            }
        }
        if ($rows && Schema::hasTable('report_task_options')) {
            DB::table('report_task_options')->insertOrIgnore(array_values($rows));
        }
    }

    public function search(string $search, array $selected = []): array
    {
        $search = self::title($search);
        $atlas = StudioRecording::query()->whereNotNull('task_title')->where('task_title', '!=', '')
            ->select('task_title')->selectRaw('MAX(recorded_at) as latest')->groupBy('task_title')->orderByDesc('latest')->get();
        $options = [];
        foreach ($atlas as $recording) {
            $title = self::title($recording->task_title);
            if ($title !== '' && mb_strlen($title) <= 200) {
                $options[self::key($title)] = ['title' => $title, 'source' => 'atlas'];
            }
        }
        // Older deployments can still read Atlas titles before the optional catalog migration is applied.
        $custom = Schema::hasTable('report_task_options') ? DB::table('report_task_options')->orderByDesc('id')->get() : [];
        foreach ($custom as $task) {
            $options[$task->normalized_key] ??= ['title' => $task->title, 'source' => 'report'];
        }
        foreach ($selected as $title) {
            $options[self::key($title)] ??= ['title' => self::title($title), 'source' => 'selected'];
        }
        $language = app(TaskLanguage::class);
        $matches = [];
        foreach ($options as $option) {
            $option += $language->describe($option['title']);
            $score = $language->score($search, $option);
            if ($score > 0) $matches[] = $option + ['score' => $score];
        }
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($matches, 0, 80);
    }
}
