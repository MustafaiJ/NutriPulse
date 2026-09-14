<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InsightGenerator
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
    ) {}

    /**
     * Generate a short, plain-language health insight paragraph from the
     * summarized dashboard data. Falls back to a deterministic summary when
     * no AI provider is configured or the call fails.
     */
    public function generate(string $summaryJson): string
    {
        if ($this->apiKey === null) {
            return $this->fallback($summaryJson);
        }

        $prompt = <<<PROMPT
You are a health coach reviewing a patient's food, blood sugar, and workout data for the last 7 days.
Write a concise, friendly insight paragraph (max 80 words), in plain text, for the patient.
Focus on patterns and practical suggestions. Reference specific values from the data. Do not invent data.
Do NOT use markdown or bullet lists. Data:
{$summaryJson}
PROMPT;

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [[
                        'parts' => [['text' => $prompt]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 300,
                    ],
                ])
                ->throw()
                ->json();

            $text = data_get($response, 'candidates.0.content.parts.0.text');

            return $text !== null && trim($text) !== '' ? trim($text) : $this->fallback($summaryJson);
        } catch (\Throwable) {
            return $this->fallback($summaryJson);
        }
    }

    private function fallback(string $summaryJson): string
    {
        $data = json_decode($summaryJson, true) ?: [];

        $totalCalories = collect($data['food_entries_last_7d'] ?? [])->sum('calories');
        $avgCalories = $data['food_entries_last_7d'] ? (int) round($totalCalories / count($data['food_entries_last_7d'])) : 0;

        $sugarValues = collect($data['blood_sugar_last_7d_mgdl'] ?? [])->pluck('value');
        $avgSugar = $sugarValues->isNotEmpty() ? round($sugarValues->avg(), 1) : null;
        $highReadings = $sugarValues->filter(fn ($v) => $v > ($data['targets']['postmeal_max'] ?? 140))->count();

        $workouts = count($data['workouts_last_7d'] ?? []);
        $target = $data['target_calories'] ?? 2000;

        $parts = [];
        $parts[] = $data['food_entries_last_7d']
            ? "Over the last 7 days you averaged about {$avgCalories} kcal per logged meal"
            : 'No food entries were logged in the last 7 days';

        if ($avgSugar !== null) {
            $parts[] = "your average blood sugar was {$avgSugar} mg/dL";
            if ($highReadings > 0) {
                $parts[] = "with {$highReadings} readings above your post-meal target";
            }
        } else {
            $parts[] = 'no blood sugar readings were logged';
        }

        $parts[] = "you logged {$workouts} workout(s)";

        return ucfirst(implode('; ', $parts).'.')
            .' Keep logging daily so your trends stay visible.'
            ." Your daily calorie target is {$target} kcal.";
    }
}
