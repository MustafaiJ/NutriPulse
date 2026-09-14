<?php

namespace App\Http\Controllers;

use App\Models\BloodSugarReading;
use App\Models\FoodEntry;
use App\Models\User;
use App\Models\Workout;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export the admin's health data to a single CSV with sections.
     * Only the admin (owner) or the dietitian (limited to food+sugar) may export.
     */
    public function csv(): StreamedResponse
    {
        $user = auth()->user();
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

        $filename = 'health-data-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($user, $admin) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Food Entries']);
            fputcsv($handle, ['Date', 'Meal', 'Description', 'Portion', 'Calories (kcal)', 'Protein (g)', 'Carbs (g)', 'Fat (g)', 'Source']);
            FoodEntry::query()
                ->where('user_id', $admin->id)
                ->orderBy('logged_at')
                ->each(function (FoodEntry $entry) use ($handle) {
                    $macros = $entry->getDisplayMacros();
                    fputcsv($handle, [
                        $entry->logged_at->format('Y-m-d H:i'),
                        $entry->meal_type,
                        $entry->description,
                        $entry->portion,
                        $entry->getDisplayCalories(),
                        $macros['protein'] ?? '',
                        $macros['carbs'] ?? '',
                        $macros['fat'] ?? '',
                        $entry->corrected_calories ? 'corrected' : 'ai',
                    ]);
                });

            fputcsv($handle, []);
            fputcsv($handle, ['Blood Sugar Readings']);
            fputcsv($handle, ['Timestamp', 'Value', 'Unit', 'Context', 'In Range']);
            BloodSugarReading::query()
                ->where('user_id', $admin->id)
                ->orderBy('logged_at')
                ->each(function (BloodSugarReading $reading) use ($handle) {
                    fputcsv($handle, [
                        $reading->logged_at->format('Y-m-d H:i'),
                        $reading->value,
                        $reading->unit,
                        $reading->context_tag,
                        $reading->isInRange() ? 'yes' : 'no',
                    ]);
                });

            // Gym data is admin only — never export it for the dietitian.
            if ($user->isAdmin()) {
                fputcsv($handle, []);
                fputcsv($handle, ['Workouts']);
                fputcsv($handle, ['Timestamp', 'Type', 'Exercise', 'Details', 'Duration (min)']);
                Workout::query()
                    ->where('user_id', $admin->id)
                    ->orderBy('logged_at')
                    ->each(function (Workout $workout) use ($handle) {
                        fputcsv($handle, [
                            $workout->logged_at->format('Y-m-d H:i'),
                            $workout->type,
                            $workout->exercise,
                            json_encode($workout->details_json, JSON_UNESCAPED_UNICODE),
                            $workout->duration_minutes,
                        ]);
                    });
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
