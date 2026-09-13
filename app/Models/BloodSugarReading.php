<?php

namespace App\Models;

use Database\Factories\BloodSugarReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'value',
    'unit',
    'context_tag',
    'logged_at',
])]
#[Casts([
    'value' => 'decimal:1',
    'logged_at' => 'datetime',
])]
class BloodSugarReading extends Model
{
    /** @use HasFactory<BloodSugarReadingFactory> */
    use HasFactory;

    public const UNITS = ['mg/dL', 'mmol/L'];
    public const CONTEXTS = ['fasting', 'post_meal', 'random', 'bedtime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}