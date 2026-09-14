<?php

namespace App\Models;

use Database\Factories\BloodSugarReadingFactory;
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
class BloodSugarReading extends Model
{
    /** @use HasFactory<BloodSugarReadingFactory> */
    use HasFactory;

    public const UNITS = ['mg/dL', 'mmol/L'];

    public const CONTEXTS = ['fasting', 'post_meal', 'random', 'bedtime'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:1',
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Normalize the reading value to mg/dL (canonical comparison unit).
     */
    public function toMgDl(): float
    {
        return $this->unit === 'mmol/L'
            ? round($this->value * 18.0182, 1)
            : (float) $this->value;
    }

    /**
     * Whether the reading falls within the configured target range for its
     * context tag (fasting / post-meal / random / bedtime).
     */
    public function isInRange(): bool
    {
        $mgDl = $this->toMgDl();
        $targets = config('health');

        return match ($this->context_tag) {
            'fasting' => $mgDl >= $targets['fasting_min'] && $mgDl <= $targets['fasting_max'],
            'post_meal' => $mgDl >= $targets['postmeal_min'] && $mgDl <= $targets['postmeal_max'],
            default => $mgDl >= $targets['random_min'] && $mgDl <= $targets['random_max'],
        };
    }
}
