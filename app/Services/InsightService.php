<?php

namespace App\Services;

use App\Models\BloodSugarReading;
use App\Models\DashboardInsight;
use App\Models\FoodEntry;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Collection;

class InsightService
{
    public function __construct(private readonly InsightGenerator $insights) {}

    /**
     * Build the complete dashboard dataset for the admin.
     *
     * @return array<string, mixed>
     */
    public function buildDashboard(User $admin): array
    {
        $todayTotals = $this->dailyTotals($admin);

        return [
            'todayCalories' => $todayTotals['calories'],
            'targetCalories' => config('health.target_calories'),
            'calorieDelta' => $todayTotals['calories'] - config('health.target_calories', 2000),
            'workoutStreak' => $this->workoutStreak($admin),
            'workoutCount7d' => Workout::query()
                ->where('user_id', $admin->id)
                ->where('logged_at', '>=', now()->subDays(7))
                ->count(),
            'avgSugar7d' => $this->averageSugar($admin, 7),
            'sugarInRange7d' => $this->sugarInRangeRatio($admin, 7),
            'latestInsight' => DashboardInsight::query()
                ->where('user_id', $admin->id)
                ->latest('generated_at')
                ->first(),
        ];
    }

    /**
     * Generate (or return the cached) AI insight paragraph for the admin.
     */
    public function insightFor(User $admin): ?DashboardInsight
    {
        $today = today()->toDateString();
        $cached = DashboardInsight::query()
            ->where('user_id', $admin->id)
            ->whereDate('generated_at', $today)
            ->latest('generated_at')
            ->first();

        if ($cached !== null) {
            return $cached;
        }

        $summary = $this->insightSummary($admin);
        $text = $this->insights->generate($summary);

        return DashboardInsight::query()->create([
            'user_id' => $admin->id,
            'insight_text' => $text,
            'generated_at' => now(),
            'source' => DashboardInsight::SOURCES[0],
        ]);
    }

    private function dailyTotals(User $admin): array
    {
        $entries = FoodEntry::query()
            ->where('user_id', $admin->id)
            ->whereDate('logged_at', today())
            ->get();

        return [
            'calories' => $entries->sum(fn (FoodEntry $entry) => $entry->getDisplayCalories() ?? 0),
            'macros' => $entries->map->{'getDisplayMacros'}()->filter()->flatMap(fn (array $m) => $m),
        ];
    }

    private function workoutStreak(User $admin): int
    {
        $dates = Workout::query()
            ->where('user_id', $admin->id)
            ->orderByDesc('logged_at')
            ->pluck('logged_at')
            ->map->toDateString()
            ->unique()
            ->values();

        $streak = 0;
        $cursor = $dates->contains(today()->toDateString()) ? today() : today()->subDay();

        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }

            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    private function averageSugar(User $admin, int $days): ?float
    {
        $readings = $this->sugarReadings($admin, $days);

        if ($readings->isEmpty()) {
            return null;
        }

        return round($readings->avg('value'), 1);
    }

    private function sugarInRangeRatio(User $admin, int $days): ?float
    {
        $readings = $this->sugarReadings($admin, $days);

        if ($readings->isEmpty()) {
            return null;
        }

        return round($readings->filter(fn (BloodSugarReading $r) => $r->isInRange())->count()
            / $readings->count() * 100);
    }

    /**
     * @return Collection<int, BloodSugarReading>
     */
    private function sugarReadings(User $admin, int $days): Collection
    {
        return BloodSugarReading::query()
            ->where('user_id', $admin->id)
            ->where('logged_at', '>=', now()->subDays($days))
            ->get();
    }

    private function insightSummary(User $admin): string
    {
        $sevenDaysAgo = now()->subDays(7);

        $food = FoodEntry::query()
            ->where('user_id', $admin->id)
            ->where('logged_at', '>=', $sevenDaysAgo)
            ->get();

        $sugar = $this->sugarReadings($admin, 7);
        $workouts = Workout::query()
            ->where('user_id', $admin->id)
            ->where('logged_at', '>=', $sevenDaysAgo)
            ->get();

        return json_encode([
            'food_entries_last_7d' => $food->map(fn (FoodEntry $entry) => [
                'date' => $entry->logged_at->toDateString(),
                'meal' => $entry->meal_type,
                'description' => $entry->description,
                'calories' => $entry->getDisplayCalories(),
            ])->values(),
            'blood_sugar_last_7d_mgdl' => $sugar->map(fn (BloodSugarReading $r) => [
                'datetime' => $r->logged_at->toDateTimeString(),
                'value' => $r->unit === 'mmol/L' ? round($r->value * 18.0182, 1) : (float) $r->value,
                'context' => $r->context_tag,
            ])->values(),
            'workouts_last_7d' => $workouts->map(fn (Workout $w) => [
                'date' => $w->logged_at->toDateString(),
                'type' => $w->type,
                'exercise' => $w->exercise,
                'details' => $w->details_json,
            ])->values(),
            'targets' => config('health'),
            'target_calories' => config('health.target_calories'),
        ], JSON_UNESCAPED_UNICODE);
    }
}
