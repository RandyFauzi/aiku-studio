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

    protected $appends = ['data_sources'];

    public function getDataSourcesAttribute(): array
    {
        $sources = [];
        if ($this->atlas_synced_at !== null) $sources[] = 'atlas';
        if ($this->robotics_1618_synced_at !== null) $sources[] = '1618';

        return $sources;
    }


    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'assigned_at' => 'datetime', 'last_synced_at' => 'datetime', 'duration_sec' => 'float', 'duration_min' => 'float', 'billable_min' => 'float', 'rejected_min' => 'float', 'pending_min' => 'float', 'deferred_min' => 'float'];
    }
}
