<?php

namespace App\Models;

use Database\Factories\FoodEntryCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'food_entry_id',
    'user_id',
    'comment',
])]
class FoodEntryComment extends Model
{
    /** @use HasFactory<FoodEntryCommentFactory> */
    use HasFactory;

    public function foodEntry(): BelongsTo
    {
        return $this->belongsTo(FoodEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}