<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime', 'gps_captured_at' => 'datetime', 'latitude' => 'float', 'longitude' => 'float', 'accuracy_m' => 'float'];
    }
}
