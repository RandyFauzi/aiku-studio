<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['evidence_data' => 'array', 'bonus' => 'integer', 'total_thp' => 'integer', 'total_logged_hours' => 'float', 'total_approved_hours' => 'float'];
    }
}
