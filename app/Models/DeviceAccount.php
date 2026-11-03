<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeviceAccount extends Model
{
    protected $fillable = [
        'phone_key',
        'email',
        'account_name',
        'is_active',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPhone(Builder $query, string $phoneKey): Builder
    {
        return $query->where('phone_key', $phoneKey);
    }

    public function getPhoneLabelAttribute(): string
    {
        return DailyWorkReport::PHONES[$this->phone_key] ?? ucfirst($this->phone_key);
    }
}
