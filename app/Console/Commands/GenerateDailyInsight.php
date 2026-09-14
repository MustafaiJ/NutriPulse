<?php

namespace App\Console\Commands;

use App\Models\DashboardInsight;
use App\Models\User;
use App\Services\InsightService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('insight:generate-daily {--force : Regenerate even if already generated today}')]
#[Description('Generate the daily AI dashboard insight for the admin')]
class GenerateDailyInsight extends Command
{
    public function handle(InsightService $service): int
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        if ($admin === null) {
            $this->warn('No admin user found; skipping.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $alreadyToday = DashboardInsight::query()
                ->where('user_id', $admin->id)
                ->whereDate('generated_at', today())
                ->exists();

            if ($alreadyToday) {
                $this->info('Insight already generated today; skipped.');

                return self::SUCCESS;
            }
        }

        $insight = $service->insightFor($admin);

        $this->info('Generated daily insight: '.$insight->insight_text);

        return self::SUCCESS;
    }
}
