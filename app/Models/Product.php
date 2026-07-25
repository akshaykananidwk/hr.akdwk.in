<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'plans' => 'array',
        'is_active' => 'boolean',
        'commission_value' => 'decimal:2',
        'renewal_commission_value' => 'decimal:2',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
