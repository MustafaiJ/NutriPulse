<?php

namespace App\Models;

use Database\Factories\DietPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'created_by',
    'title',
    'content',
    'target_calories',
    'target_macros',
    'active_from',
    'notes',
])]
#[Casts([
    'target_macros' => 'array',
    'active_from' => 'date',
])]
class DietPlan extends Model
{
    /** @use HasFactory<DietPlanFactory> */
    use HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}