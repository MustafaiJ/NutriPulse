<?php

namespace App\Http\Controllers;

use App\Models\FoodEntry;
use App\Models\FoodEntryComment;
use App\Models\User;
use App\Services\NutritionEstimator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FoodEntryController extends Controller
{
    public function __construct(private readonly NutritionEstimator $estimator)
    {
    }

    public function index(Request $request): View
    {
        $date = $request->date ?: today()->toDateString();
        $admin = $this->adminUser();

        $entries = FoodEntry::query()
            ->where('user_id', $admin->id)
            ->whereDate('logged_at', $date)
            ->orderByDesc('logged_at')
            ->with('comments.user')
            ->get();

        $weekStart = today()->subDays(6)->startOfDay();
        $weeklyCalories = FoodEntry::query()
            ->where('user_id', $admin->id)
            ->whereBetween('logged_at', [$weekStart, now()])
            ->get()
            ->groupBy(fn (FoodEntry $entry) => $entry->logged_at->toDateString())
            ->map(
                fn ($entries) => $entries->sum(fn (FoodEntry $entry) => $entry->getDisplayCalories() ?? 0)
            );

        return view('food.index', [
            'user' => $request->user(),
            'admin' => $admin,
            'entries' => $entries,
            'date' => $date,
            'dailyCalories' => $entries->sum(fn (FoodEntry $entry) => $entry->getDisplayCalories() ?? 0),
            'weeklyCalories' => $weeklyCalories,
        ]);
    }

    public function create(): View
    {
        return view('food.create', [
            'meals' => FoodEntry::MEALS,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'meal_type' => ['required', 'in:'.implode(',', FoodEntry::MEALS)],
            'description' => ['required', 'string', 'max:255'],
            'portion' => ['nullable', 'string', 'max:255'],
            'logged_at' => ['required', 'date'],
        ]);

        $estimates = $this->estimator->estimate($request->description, $request->portion);

        FoodEntry::create([
            'user_id' => $request->user()->id,
            'meal_type' => $request->meal_type,
            'description' => $request->description,
            'portion' => $request->portion,
            'ai_calories' => $estimates['calories'] ?? null,
            'ai_macros' => $estimates['macros'] ?? null,
            'logged_at' => $request->logged_at,
        ]);

        return redirect()
            ->route('food.index', ['date' => date('Y-m-d', strtotime($request->logged_at))])
            ->with('status', 'Food entry added.');
    }

    public function edit(FoodEntry $entry): View
    {
        abort_unless($entry->user_id === $this->adminUser()->id, 403);

        return view('food.edit', [
            'entry' => $entry,
            'meals' => FoodEntry::MEALS,
        ]);
    }

    public function update(Request $request, FoodEntry $entry)
    {
        abort_unless($entry->user_id === $this->adminUser()->id, 403);

        $request->validate([
            'meal_type' => ['required', 'in:'.implode(',', FoodEntry::MEALS)],
            'description' => ['required', 'string', 'max:255'],
            'portion' => ['nullable', 'string', 'max:255'],
            'corrected_calories' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'corrected_macros.protein' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'corrected_macros.carbs' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'corrected_macros.fat' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $entry->update([
            'meal_type' => $request->meal_type,
            'description' => $request->description,
            'portion' => $request->portion,
            'corrected_calories' => $request->filled('corrected_calories') ? $request->corrected_calories : $entry->corrected_calories,
            'corrected_macros' => $request->filled('corrected_macros.protein')
                ? [
                    'protein' => $request->corrected_macros['protein'] ?? $entry->getDisplayMacros()['protein'] ?? null,
                    'carbs' => $request->corrected_macros['carbs'] ?? $entry->getDisplayMacros()['carbs'] ?? null,
                    'fat' => $request->corrected_macros['fat'] ?? $entry->getDisplayMacros()['fat'] ?? null,
                ]
                : $entry->corrected_macros,
        ]);

        return redirect()
            ->route('food.index', ['date' => $entry->logged_at->toDateString()])
            ->with('status', 'Food entry updated.');
    }

    public function destroy(FoodEntry $entry)
    {
        abort_unless($entry->user_id === $this->adminUser()->id, 403);

        $entry->delete();

        return redirect()
            ->route('food.index')
            ->with('status', 'Food entry deleted.');
    }

    public function comment(Request $request, FoodEntry $entry)
    {
        abort_unless($request->user()->isDietitian(), 403);

        $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        FoodEntryComment::create([
            'food_entry_id' => $entry->id,
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
        ]);

        return redirect()
            ->route('food.index', ['date' => $entry->logged_at->toDateString()])
            ->with('status', 'Comment added.');
    }

    private function adminUser(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }
}