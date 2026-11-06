<?php

namespace App\Services;

use App\Models\StudioRecording;
use Illuminate\Support\Facades\DB;
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
        if ($rows) {
            DB::table('report_task_options')->insertOrIgnore(array_values($rows));
        }
    }

    public function search(string $search): array
    {
        $search = self::title($search);
        $atlas = StudioRecording::query()->whereNotNull('task_title')->where('task_title', '!=', '')
            ->when($search !== '', fn ($q) => $q->where('task_title', 'like', '%'.$search.'%'))
            ->select('task_title')->selectRaw('MAX(recorded_at) as latest')->groupBy('task_title')->orderByDesc('latest')->limit(60)->get();
        $options = [];
        foreach ($atlas as $recording) {
            $title = self::title($recording->task_title);
            if ($title !== '' && mb_strlen($title) <= 200) {
                $options[self::key($title)] = ['title' => $title, 'source' => 'atlas'];
            }
        }
        $custom = DB::table('report_task_options')->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->limit(60)->get();
        foreach ($custom as $task) {
            $options[$task->normalized_key] ??= ['title' => $task->title, 'source' => 'report'];
        }
        return array_slice(array_values($options), 0, 60);
    }
}
