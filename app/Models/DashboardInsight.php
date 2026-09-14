<?php

namespace App\Models;

use Database\Factories\DashboardInsightFactory;
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
class DashboardInsight extends Model
{
    /** @use HasFactory<DashboardInsightFactory> */
    use HasFactory;

    public const SOURCES = ['ai', 'manual'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
