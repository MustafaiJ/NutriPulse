<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkoutController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->get('days', 30);
        $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;

        $workouts = Workout::query()
            ->where('user_id', $request->user()->id)
            ->where('logged_at', '>=', now()->subDays($days))
            ->orderByDesc('logged_at')
            ->get();

        $recent = $workouts->take(14)->reverse();

        $byExercise = $workouts
            ->where('type', 'strength')
            ->groupBy('exercise')
            ->map(fn ($rows) => $rows->map(fn (Workout $w) => [
                'date' => $w->logged_at->format('M j'),
                'weight' => (float) ($w->details_json['weight_kg'] ?? 0),
                'sets' => (int) ($w->details_json['sets'] ?? 0),
                'reps' => (int) ($w->details_json['reps'] ?? 0),
            ]));

        $frequency = $workouts
            ->groupBy(fn (Workout $w) => $w->logged_at->toDateString())
            ->map->count();

        return view('workouts.index', [
            'workouts' => $workouts,
            'recent' => $recent,
            'byExercise' => $byExercise,
            'frequency' => $frequency,
            'days' => $days,
            'totalDuration' => $workouts->sum('duration_minutes'),
            'streak' => $this->currentStreak($request->user()->id),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', Workout::TYPES)],
            'exercise' => ['nullable', 'string', 'max:255', 'required_if:type,strength'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'sets' => ['nullable', 'integer', 'min:1', 'max:20'],
            'reps' => ['nullable', 'integer', 'min:1', 'max:100'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'logged_at' => ['required', 'date'],
        ]);

        $details = $request->type === 'strength'
            ? [
                'sets' => (int) $request->sets,
                'reps' => (int) $request->reps,
                'weight_kg' => (float) $request->weight_kg,
            ]
            : [
                'distance_km' => (float) $request->distance_km,
            ];

        Workout::create([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'exercise' => $request->type === 'strength' ? $request->exercise : null,
            'details_json' => $details,
            'duration_minutes' => $request->duration_minutes ?: null,
            'logged_at' => $request->logged_at,
        ]);

        return redirect()->route('workouts.index')->with('status', 'Workout logged.');
    }

    public function edit(Workout $workout): View
    {
        abort_unless($workout->user_id === auth()->id(), 403);

        return view('workouts.edit', ['workout' => $workout]);
    }

    public function update(Request $request, Workout $workout)
    {
        abort_unless($workout->user_id === auth()->id(), 403);

        $request->validate([
            'type' => ['required', 'in:'.implode(',', Workout::TYPES)],
            'exercise' => ['nullable', 'string', 'max:255', 'required_if:type,strength'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'sets' => ['nullable', 'integer', 'min:1', 'max:20'],
            'reps' => ['nullable', 'integer', 'min:1', 'max:100'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);

        $details = $request->type === 'strength'
            ? [
                'sets' => (int) $request->sets,
                'reps' => (int) $request->reps,
                'weight_kg' => (float) $request->weight_kg,
            ]
            : [
                'distance_km' => (float) $request->distance_km,
            ];

        $workout->update([
            'type' => $request->type,
            'exercise' => $request->type === 'strength' ? $request->exercise : null,
            'details_json' => $details,
            'duration_minutes' => $request->duration_minutes ?: null,
        ]);

        return redirect()->route('workouts.index')->with('status', 'Workout updated.');
    }

    public function destroy(Workout $workout)
    {
        abort_unless($workout->user_id === auth()->id(), 403);

        $workout->delete();

        return redirect()->route('workouts.index')->with('status', 'Workout deleted.');
    }

    private function currentStreak(int $userId): int
    {
        $dates = Workout::query()
            ->where('user_id', $userId)
            ->orderByDesc('logged_at')
            ->pluck('logged_at')
            ->map(fn ($ts) => $ts->toDateString())
            ->unique()
            ->values();

        $streak = 0;
        // If the user hasn't logged today, the streak can still continue from yesterday.
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
}