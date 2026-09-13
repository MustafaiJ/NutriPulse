<?php

namespace App\Services;

use App\Contracts\AiProvider;

class NutritionEstimator
{
    public function __construct(private readonly AiProvider $provider)
    {
    }

    /**
     * Estimate nutrition from a food description via the configured AI provider.
     *
     * @return array{calories: int, macros: array{protein: int, carbs: int, fat: int}}
     */
    public function estimate(string $description, ?string $portion = null): array
    {
        try {
            return $this->provider->estimateNutrition($description, $portion);
        } catch (\Throwable) {
            return $this->fallback($description);
        }
    }

    /**
     * Best-effort heuristics used when the AI provider is unavailable
     * (no API key, network failure, etc.) so logging always completes.
     *
     * @return array{calories: int, macros: array{protein: int, carbs: int, fat: int}}
     */
    public function fallback(string $description): array
    {
        $words = str_word_count($description);
        $calories = max(100, $words * 40);

        return [
            'calories' => $calories,
            'macros' => [
                'protein' => (int) round($calories * 0.2 / 4),
                'carbs' => (int) round($calories * 0.5 / 4),
                'fat' => (int) round($calories * 0.3 / 9),
            ],
        ];
    }
}