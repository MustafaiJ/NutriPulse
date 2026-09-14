<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\InsightService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly InsightService $insights) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isDietitian()) {
            return view('dashboard-dietitian');
        }

        return view('dashboard', $this->insights->buildDashboard($user));
    }

    /**
     * Generate the AI insight on demand (admin only).
     */
    public function insight(Request $request)
    {
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

        return response()->json([
            'insight' => $this->insights->insightFor($admin)->insight_text,
        ]);
    }
}
