<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contributor extends Model
{
    public $timestamps = false;

    public const BADGE_COLORS = [
        'violet',
        'blue',
        'rose',
        'cyan',
        'emerald',
        'amber',
        'purple',
        'indigo',
        'pink',
        'teal',
        'orange',
        'lime',
        'sky',
        'fuchsia',
        'slate',
    ];

    protected $fillable = ['nama', 'no_wa', 'domisili', 'no_rek', 'shift', 'hp', 'email', 'role', 'badge_color'];

    protected $attributes = ['role' => 'contributor'];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
