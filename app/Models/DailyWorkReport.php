<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyWorkReport extends Model
{
    public const PHONES = ['putih' => 'Putih', 'pink' => 'Pink', 'biru' => 'Biru', 'hijau' => 'Hijau', 'hitam' => 'Hitam', 'pink_st' => 'Pink ST'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'phone_durations' => 'array',
            'phone_emails' => 'array',
            'total_duration_minutes' => 'integer',
            'revision' => 'integer',
        ];
    }
}
