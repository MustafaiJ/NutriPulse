<?php

namespace App\Http\Controllers;

use App\Models\TravelPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TravelPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $admin = $user->isAdmin() ? $user : User::where('role', User::ROLE_ADMIN)->firstOrFail();

        $periods = TravelPeriod::query()
            ->where('user_id', $admin->id)
            ->orderByDesc('start_date')
            ->get();

        $active = $periods
            ->filter(fn (TravelPeriod $p) => $p->start_date <= today() && $p->end_date >= today())
            ->first();

        $upcoming = $periods->filter(fn (TravelPeriod $p) => $p->start_date > today());

        return view('travel.index', [
            'periods' => $periods,
            'active' => $active,
            'upcoming' => $upcoming,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        TravelPeriod::create([
            'user_id' => $request->user()->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
        ]);

        return back()->with('status', 'Travel period saved.');
    }

    public function destroy(TravelPeriod $period)
    {
        abort_if($period->user_id !== auth()->id(), 403);

        $period->delete();

        return back()->with('status', 'Travel period removed.');
    }
}