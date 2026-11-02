<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioRecording extends Model
{
    protected $primaryKey = 'record_id';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'createdAt';

    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'assigned_at' => 'datetime', 'last_synced_at' => 'datetime', 'duration_sec' => 'float', 'duration_min' => 'float', 'billable_min' => 'float', 'rejected_min' => 'float', 'pending_min' => 'float', 'deferred_min' => 'float'];
    }
}
