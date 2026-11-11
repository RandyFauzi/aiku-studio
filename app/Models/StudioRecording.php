<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class StudioRecording extends Model
{
    protected $primaryKey = 'record_id';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'createdAt';

    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected $appends = ['data_sources', 'qc_delta'];

    public function getDataSourcesAttribute(): array
    {
        $sources = [];
        if ($this->atlas_synced_at !== null) $sources[] = 'atlas';
        if ($this->robotics_1618_synced_at !== null) $sources[] = '1618';

        return $sources;
    }

    public function getQcDeltaAttribute(): ?array
    {
        if (! $this->qc_changed_at || ($this->prev_billable_min === null && $this->prev_rejected_min === null)) {
            return null;
        }

        $prevBillable = $this->prev_billable_min !== null ? (float) $this->prev_billable_min : (float) $this->billable_min;
        $prevRejected = $this->prev_rejected_min !== null ? (float) $this->prev_rejected_min : (float) $this->rejected_min;

        $diffBillable = round((float) $this->billable_min - $prevBillable, 2);
        $diffRejected = round((float) $this->rejected_min - $prevRejected, 2);

        if (abs($diffBillable) < 0.01 && abs($diffRejected) < 0.01) {
            return null;
        }

        return [
            'billable_diff' => $diffBillable,
            'billable_direction' => $diffBillable > 0 ? 'up' : ($diffBillable < 0 ? 'down' : 'none'),
            'rejected_diff' => $diffRejected,
            'rejected_direction' => $diffRejected > 0 ? 'up' : ($diffRejected < 0 ? 'down' : 'none'),
            'prev_billable' => $prevBillable,
            'prev_rejected' => $prevRejected,
            'current_billable' => (float) $this->billable_min,
            'current_rejected' => (float) $this->rejected_min,
            'changed_at' => $this->qc_changed_at?->toIso8601String(),
            'changed_at_formatted' => $this->qc_changed_at ? CarbonImmutable::parse($this->qc_changed_at)->setTimezone('Asia/Makassar')->format('d M H:i').' WITA' : null,
        ];
    }

    public static function ingestWithQcTracking(string $recordId, array $values): self
    {
        $existing = static::find($recordId);

        if ($existing) {
            $newBillable = array_key_exists('billable_min', $values) ? (float) $values['billable_min'] : (float) $existing->billable_min;
            $newRejected = array_key_exists('rejected_min', $values) ? (float) $values['rejected_min'] : (float) $existing->rejected_min;

            $oldBillable = (float) $existing->billable_min;
            $oldRejected = (float) $existing->rejected_min;

            $billableChanged = abs($oldBillable - $newBillable) >= 0.01;
            $rejectedChanged = abs($oldRejected - $newRejected) >= 0.01;

            if ($billableChanged || $rejectedChanged) {
                $values['prev_billable_min'] = $oldBillable;
                $values['prev_rejected_min'] = $oldRejected;
                $values['qc_changed_at'] = now();
            }
        }

        return static::updateOrCreate(['record_id' => $recordId], $values);
    }

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'assigned_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'qc_changed_at' => 'datetime',
            'duration_sec' => 'float',
            'duration_min' => 'float',
            'billable_min' => 'float',
            'rejected_min' => 'float',
            'prev_billable_min' => 'float',
            'prev_rejected_min' => 'float',
            'pending_min' => 'float',
            'deferred_min' => 'float',
        ];
    }
}
