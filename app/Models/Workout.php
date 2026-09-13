<?php

namespace App\Models;

use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
    'exercise',
    'details_json',
    'duration_minutes',
    'logged_at',
])]
#[Casts([
    'details_json' => 'array',
    'logged_at' => 'datetime',
])]
class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;

    public const TYPES = ['strength', 'cardio', 'hiit', 'stretching'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}