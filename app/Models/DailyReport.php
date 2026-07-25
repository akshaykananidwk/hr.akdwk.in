<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'total_collection' => 'decimal:2',
        'expenses' => 'decimal:2',
        'petrol_expense' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ReportVisit::class);
    }
}
