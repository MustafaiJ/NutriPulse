<?php

namespace App\Models;

use Database\Factories\DashboardInsightFactory;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'insight_text',
    'generated_at',
    'source',
])]
#[Casts([
    'generated_at' => 'datetime',
])]
class DashboardInsight extends Model
{
    /** @use HasFactory<DashboardInsightFactory> */
    use HasFactory;

    public const SOURCES = ['ai', 'manual'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}