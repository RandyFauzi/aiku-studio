<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordStreakDay extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['schedule_snapshot' => 'array', 'evidence_ids' => 'array', 'deadline_at' => 'immutable_datetime',
            'celebrated_at' => 'immutable_datetime', 'verified_at' => 'immutable_datetime',
            'target_minutes' => 'integer', 'manual_report_revision' => 'integer', 'manual_report_id' => 'integer',
            'verified_by' => 'integer', 'verified_minutes' => 'float'];
    }
}
