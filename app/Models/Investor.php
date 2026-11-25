<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    protected $fillable = [
        'code',
        'name',
        'no_wa',
        'email',
        'bank_name',
        'no_rek',
        'account_holder',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(InvestorDeviceContract::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(InvestorPayout::class);
    }
}
