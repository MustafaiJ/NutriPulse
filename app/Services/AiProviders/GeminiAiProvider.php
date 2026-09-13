<?php

namespace App\Services\AiProviders;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

/**
 * Google Gemini Flash (cheap-tier) provider. Calls the REST API directly with
 * HTTP, which works fine on shared cPanel hosting. JSON mode is used so the
 * response parses reliably.
 */
class GeminiAiProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    public function estimateNutrition(string $description, ?string $portion = null): array
    {
        $prompt = <<<PROMPT
You are a nutritionist. Estimate the calories and macronutrients (protein, carbs, fat in grams) for this meal description. Respond ONLY with valid JSON in this exact shape: {"calories": 450, "macros": {"protein": 20, "carbs": 60, "fat": 15}}. Use realistic whole numbers.

Description: {$description}
PROMPT;

        if ($portion !== null) {
            $prompt .= "\nPortion / serving size: {$portion}";
        }

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                ],
            ])
            ->throw()
            ->json();

        $text = data_get($response, 'candidates.0.content.parts.0.text');

        if ($text === null) {
            return $this->fallback($description);
        }

        $data = json_decode($text, true) ?: [];

        $macros = $data['macros'] ?? [];

        return [
            'calories' => (int) ($data['calories'] ?? 0) ?: $this->fallback($description)['calories'],
            'macros' => [
                'protein' => (int) ($macros['protein'] ?? 0) ?: 0,
                'carbs' => (int) ($macros['carbs'] ?? 0) ?: 0,
                'fat' => (int) ($macros['fat'] ?? 0) ?: 0,
            ],
        ];
    }

    private function fallback(string $description): array
    {
        return (new HeuristicAiProvider)->estimateNutrition($description);
    }
}