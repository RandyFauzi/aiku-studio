<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contributor extends Model
{
    public $timestamps = false;

    protected $fillable = ['nama', 'no_wa', 'domisili', 'no_rek', 'shift', 'hp', 'email'];
}
