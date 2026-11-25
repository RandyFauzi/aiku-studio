<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorDeviceContract extends Model
{
    protected $fillable = [
        'investor_id',
        'device_key',
        'device_code',
        'rate_per_hour_idr',
        'rate_basis',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_hour_idr' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
