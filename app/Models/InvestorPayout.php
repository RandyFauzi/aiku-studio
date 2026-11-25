<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorPayout extends Model
{
    protected $fillable = [
        'investor_id',
        'period_id',
        'period_name',
        'total_billable_hours',
        'total_amount_idr',
        'status',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_billable_hours' => 'float',
            'total_amount_idr' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
