<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSetting extends Model
{
    const CREATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['setting_value' => 'array'];
    }
}
