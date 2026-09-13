<?php

namespace App\Contracts;

interface AiProvider
{
    /**
     * Estimate calories and macros from a free-text food description.
     *
     * @return array{calories: int, macros: array{protein: int, carbs: int, fat: int}}
     */
    public function estimateNutrition(string $description, ?string $portion = null): array;
}