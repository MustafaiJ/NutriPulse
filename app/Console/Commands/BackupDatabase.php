<?php

namespace App\Console\Commands;

use App\Models\BloodSugarReading;
use App\Models\DashboardInsight;
use App\Models\DietPlan;
use App\Models\FoodEntry;
use App\Models\FoodEntryComment;
use App\Models\TravelPeriod;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('backup:database {--keep=14 : Number of backups to keep}')]
#[Description('Snapshot all app data into a dated, gzipped JSON backup')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'schema_version' => 1,
            'data' => [
                'users' => User::all(),
                'food_entries' => FoodEntry::all(),
                'food_entry_comments' => FoodEntryComment::all(),
                'blood_sugar_readings' => BloodSugarReading::all(),
                'workouts' => Workout::all(),
                'diet_plans' => DietPlan::all(),
                'travel_periods' => TravelPeriod::all(),
                'dashboard_insights' => DashboardInsight::all(),
            ],
        ];

        $filename = 'backups/db-'.now()->format('Y-m-d-His').'.json.gz';

        Storage::disk('local')->put(
            $filename,
            gzencode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 9)
        );

        $this->info("Backup written to storage/app/{$filename}");

        $keep = max(1, (int) $this->option('keep'));
        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $f) => str_ends_with($f, '.json.gz'))
            ->sortDesc()
            ->values();

        if ($files->count() > $keep) {
            foreach ($files->slice($keep) as $old) {
                Storage::disk('local')->delete($old);
                $this->comment("Pruned old backup: {$old}");
            }
        }

        return self::SUCCESS;
    }
}
