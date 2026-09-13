<?php

namespace App\Models;

use Database\Factories\TravelPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'start_date',
    'end_date',
    'notes',
])]
#[Casts([
    'start_date' => 'date',
    'end_date' => 'date',
])]
class TravelPeriod extends Model
{
    /** @use HasFactory<TravelPeriodFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}