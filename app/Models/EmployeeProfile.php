<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'basic_salary' => 'decimal:2',
        'mobile_allowance' => 'decimal:2',
        'petrol_allowance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
