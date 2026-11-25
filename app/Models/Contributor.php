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

    public static function isValidBadgeColor(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array($value, self::BADGE_COLORS, true)
            || (bool) preg_match('/^#[0-9a-f]{6}$/i', $value);
    }

    protected $attributes = ['role' => 'contributor'];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
