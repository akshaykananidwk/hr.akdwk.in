<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVisit extends Model
{
    protected $guarded = [];

    protected $casts = ['expected_closing_date' => 'date', 'next_followup_date' => 'date'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }
}
