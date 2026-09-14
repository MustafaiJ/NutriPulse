<?php

namespace App\Http\Controllers;

use App\Models\BloodSugarReading;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodSugarController extends Controller
{
    public function index(Request $request): View
    {
        $admin = $this->adminUser();
        $days = (int) $request->get('days', 30);
        $days = in_array($days, [7, 14, 30, 90], true) ? $days : 30;

        $readings = BloodSugarReading::query()
            ->where('user_id', $admin->id)
            ->where('logged_at', '>=', now()->subDays($days))
            ->orderBy('logged_at')
            ->get();

        $latest = BloodSugarReading::query()
            ->where('user_id', $admin->id)
            ->when($request->user()->isDietitian(), fn ($query) => $query->where('logged_at', '>=', now()->subDays(90)))
            ->orderByDesc('logged_at')
            ->first();

        $todayCount = BloodSugarReading::query()
            ->where('user_id', $admin->id)
            ->whereDate('logged_at', today())
            ->count();

        $rangeCount = BloodSugarReading::query()
            ->where('user_id', $admin->id)
            ->where('logged_at', '>=', now()->subDays($days))
            ->get()
            ->filter(fn (BloodSugarReading $r) => ! $r->isInRange())
            ->count();

        return view('blood-sugar.index', [
            'user' => $request->user(),
            'readings' => $readings,
            'latest' => $latest,
            'todayCount' => $todayCount,
            'outOfRangeCount' => $rangeCount,
            'days' => $days,
            'targets' => config('health'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'value' => ['required', 'numeric', 'min:20', 'max:600'],
            'unit' => ['required', 'in:'.implode(',', BloodSugarReading::UNITS)],
            'context_tag' => ['required', 'in:'.implode(',', BloodSugarReading::CONTEXTS)],
            'logged_at' => ['required', 'date'],
        ]);

        BloodSugarReading::create([
            'user_id' => $this->adminUser()->id,
            'value' => $request->value,
            'unit' => $request->unit,
            'context_tag' => $request->context_tag,
            'logged_at' => $request->logged_at,
        ]);

        return redirect()->route('blood-sugar.index')->with('status', 'Reading added.');
    }

    public function destroy(BloodSugarReading $reading)
    {
        abort_unless($reading->user_id === $this->adminUser()->id, 403);

        $reading->delete();

        return redirect()->route('blood-sugar.index')->with('status', 'Reading deleted.');
    }

    private function adminUser(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }
}
