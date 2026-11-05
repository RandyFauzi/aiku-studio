<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'evidence_data' => 'array',
            'bonus' => 'integer',
            'pending_carry_over_amount' => 'integer',
            'pending_carry_over_hours' => 'float',
            'pending_carry_over_data' => 'array',
            'is_locked' => 'boolean',
            'total_thp' => 'integer',
            'total_logged_hours' => 'float',
            'total_approved_hours' => 'float',
        ];
    }
}
