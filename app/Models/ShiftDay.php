<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftDay extends Model
{
    protected $fillable = ['work_date', 'config', 'revision'];

    protected function casts(): array
    {
        return ['config' => 'array', 'revision' => 'integer'];
    }
}
