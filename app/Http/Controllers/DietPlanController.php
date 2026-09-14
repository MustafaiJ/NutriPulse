<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\User;
use App\Notifications\DietPlanUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DietPlanController extends Controller
{
    public function index(): View
    {
        $plans = DietPlan::query()
            ->with('createdBy')
            ->orderByDesc('active_from')
            ->orderByDesc('created_at')
            ->get();

        $current = $plans
            ->filter(fn (DietPlan $plan) => $plan->active_from !== null && $plan->active_from <= today())
            ->first();

        return view('diet-plans.index', [
            'plans' => $plans,
            'current' => $current,
        ]);
    }

    public function create(): View
    {
        return view('diet-plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_calories' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'target_macros.protein' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'target_macros.carbs' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'target_macros.fat' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'active_from' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = DietPlan::create([
            'created_by' => Auth::id(),
            'content' => $request->content,
            'target_calories' => $request->target_calories,
            'target_macros' => $request->target_calories
                ? [
                    'protein' => $request->target_macros['protein'] ?? null,
                    'carbs' => $request->target_macros['carbs'] ?? null,
                    'fat' => $request->target_macros['fat'] ?? null,
                ]
                : null,
            'active_from' => $request->active_from,
            'notes' => $request->notes,
        ]);

        $admin = User::where('role', User::ROLE_ADMIN)->first();

        if ($admin !== null) {
            $admin->notify(new DietPlanUpdated($plan, 'created'));
        }

        return redirect()->route('diet-plans.index')->with('status', 'Diet plan published.');
    }

    public function edit(DietPlan $plan): View
    {
        abort_unless($plan->created_by === Auth::id(), 403);

        return view('diet-plans.edit', ['plan' => $plan]);
    }

    public function update(Request $request, DietPlan $plan)
    {
        abort_unless($plan->created_by === Auth::id(), 403);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_calories' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'target_macros.protein' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'target_macros.carbs' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'target_macros.fat' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'active_from' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan->update([
            'content' => $request->content,
            'target_calories' => $request->target_calories,
            'target_macros' => $request->target_calories
                ? [
                    'protein' => $request->target_macros['protein'] ?? null,
                    'carbs' => $request->target_macros['carbs'] ?? null,
                    'fat' => $request->target_macros['fat'] ?? null,
                ]
                : null,
            'active_from' => $request->active_from,
            'notes' => $request->notes,
        ]);

        $admin = User::where('role', User::ROLE_ADMIN)->first();

        if ($admin !== null) {
            $admin->notify(new DietPlanUpdated($plan, 'updated'));
        }

        return redirect()->route('diet-plans.index')->with('status', 'Diet plan updated.');
    }

    public function destroy(DietPlan $plan)
    {
        abort_unless($plan->created_by === Auth::id(), 403);

        $plan->delete();

        return redirect()->route('diet-plans.index')->with('status', 'Diet plan deleted.');
    }
}
