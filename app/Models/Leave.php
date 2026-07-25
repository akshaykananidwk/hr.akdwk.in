<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $guarded = [];

    protected $casts = ['from_date' => 'date', 'to_date' => 'date', 'half_day' => 'boolean', 'actioned_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getDaysAttribute(): float
    {
        if ($this->half_day) {
            return 0.5;
        }

        return $this->from_date->diffInDays($this->to_date) + 1;
    }
}
