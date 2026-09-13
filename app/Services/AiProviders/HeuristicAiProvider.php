<?php

namespace App\Services\AiProviders;

use App\Contracts\AiProvider;

/**
 * Cheap, dependency-free heuristic estimate used when no AI provider is
 * configured (e.g., local development). This is the intended fallback so the
 * app is fully usable without an API key.
 */
class HeuristicAiProvider implements AiProvider
{
    public function estimateNutrition(string $description, ?string $portion = null): array
    {
        $words = str_word_count($description) + 1;
        $scale = $portion !== null ? 1.25 : 1.0;

        $calories = (int) round(max(100, $words * 65) * $scale);

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