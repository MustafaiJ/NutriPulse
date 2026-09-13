<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\FoodEntryFactory;

#[Fillable([
    'user_id',
    'meal_type',
    'description',
    'portion',
    'ai_calories',
    'ai_macros',
    'corrected_calories',
    'corrected_macros',
    'logged_at',
])]
#[Casts([
    'ai_macros' => 'array',
    'corrected_macros' => 'array',
    'logged_at' => 'datetime',
])]
class FoodEntry extends Model
{
    /** @use HasFactory<FoodEntryFactory> */
    use HasFactory;

    public const MEALS = ['breakfast', 'lunch', 'dinner', 'snack'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FoodEntryComment::class);
    }

    public function getDisplayCalories(): ?int
    {
        return $this->corrected_calories ?? $this->ai_calories;
    }

    public function getDisplayMacros(): ?array
    {
        return $this->corrected_macros ?? $this->ai_macros;
    }
}