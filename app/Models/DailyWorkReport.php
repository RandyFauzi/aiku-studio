<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyWorkReport extends Model
{
    public const PHONES = ['putih' => 'Putih', 'pink' => 'Pink', 'biru' => 'Biru', 'hijau' => 'Hijau', 'hitam' => 'Hitam', 'pink_st' => 'Pink ST'];

    public const REPORT_PHONES = self::PHONES + ['sendiri' => 'Sendiri'];

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (self $report) {
            if ($report->wasChanged('phone_tasks') || $report->wasRecentlyCreated) {
                app(\App\Services\ReportTasks::class)->remember($report->phone_tasks ?? []);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'phone_durations' => 'array',
            'phone_emails' => 'array',
            'phone_tasks' => 'array',
            'total_duration_minutes' => 'integer',
            'revision' => 'integer',
        ];
    }
}
