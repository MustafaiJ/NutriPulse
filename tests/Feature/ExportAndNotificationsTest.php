<?php

namespace Tests\Feature;

use App\Models\BloodSugarReading;
use App\Models\DietPlan;
use App\Models\FoodEntry;
use App\Models\User;
use App\Models\Workout;
use App\Notifications\DietPlanUpdated;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExportAndNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_export_csv_with_all_sections(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        FoodEntry::factory()->for($admin)->create(['description' => 'Oats']);
        BloodSugarReading::factory()->for($admin)->create(['value' => 95]);
        Workout::factory()->for($admin)->create(['exercise' => 'Squat']);

        $response = $this->get(route('export.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Food Entries', $content);
        $this->assertStringContainsString('Oats', $content);
        $this->assertStringContainsString('Blood Sugar Readings', $content);
        $this->assertStringContainsString('Workouts', $content);
        $this->assertStringContainsString('Squat', $content);
    }

    public function test_dietitian_export_excludes_workouts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dietitian = User::factory()->create(['role' => User::ROLE_DIETITIAN]);
        $this->actingAs($dietitian);

        Workout::factory()->for($admin)->create(['exercise' => 'Bench Press']);

        $response = $this->get(route('export.csv'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Food Entries', $content);
        $this->assertStringNotContainsString('Workouts', $content);
        $this->assertStringNotContainsString('Bench Press', $content);
    }

    public function test_dietitian_plan_update_notifies_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dietitian = User::factory()->create(['role' => User::ROLE_DIETITIAN]);
        $this->actingAs($dietitian);

        $plan = DietPlan::factory()->create([
            'created_by' => $dietitian->id,
            'title' => 'Weight Loss Plan',
        ]);

        $this->patch(route('diet-plans.update', $plan), [
            'title' => 'Weight Loss Plan v2',
            'content' => 'Eat clean.',
            'target_calories' => 1800,
        ])->assertRedirect(route('diet-plans.index'));

        Notification::assertSentTo($admin, DietPlanUpdated::class);
    }

    public function test_admin_notifications_page_shows_read_and_unread(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dietitian = User::factory()->create(['role' => User::ROLE_DIETITIAN]);

        $plan = DietPlan::factory()->create([
            'created_by' => $dietitian->id,
            'title' => 'Maintenance Plan',
        ]);

        $admin->notify(new DietPlanUpdated($plan, 'created'));
        $admin->notifications()->first()->markAsRead();

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Maintenance Plan');
    }

    public function test_notification_mark_read_clears_unread_flag(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dietitian = User::factory()->create(['role' => User::ROLE_DIETITIAN]);

        $plan = DietPlan::factory()->create([
            'created_by' => $dietitian->id,
            'title' => 'Taper Plan',
        ]);

        $admin->notify(new DietPlanUpdated($plan, 'created'));
        $notification = $admin->notifications()->first();

        $this->actingAs($admin)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_daily_insight_command_schedules_later_than_manual(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->post(route('dashboard.insight'))
            ->assertOk();

        // Second same-day call is a no-op (cached daily).
        $this->post(route('dashboard.insight'))
            ->assertOk();

        $this->assertDatabaseCount('dashboard_insights', 1);
    }

    public function test_dietitian_food_view_limited_to_last_90_days(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dietitian = User::factory()->create(['role' => User::ROLE_DIETITIAN]);
        $this->actingAs($dietitian);

        $recent = now()->subDays(10);
        $old = now()->subDays(120);

        FoodEntry::factory()->for($admin)->create([
            'description' => 'Recent meal',
            'logged_at' => $recent,
        ]);
        FoodEntry::factory()->for($admin)->create([
            'description' => 'Old meal',
            'logged_at' => $old,
        ]);

        $this->get(route('food.index', ['date' => $old->toDateString()]))
            ->assertOk()
            ->assertDontSee('Old meal');

        $this->get(route('food.index', ['date' => $recent->toDateString()]))
            ->assertOk()
            ->assertSee('Recent meal');
    }

    public function test_admin_food_view_shows_all_dates(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $old = now()->subDays(120);

        FoodEntry::factory()->for($admin)->create([
            'description' => 'Ancient meal',
            'logged_at' => $old,
        ]);

        $this->get(route('food.index', ['date' => $old->toDateString()]))
            ->assertOk()
            ->assertSee('Ancient meal');

        $this->get(route('food.index'))
            ->assertOk()
            ->assertSee('No food entries for this date.');
    }

    public function test_food_correction_can_be_cleared_to_revert_to_ai(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $entry = FoodEntry::factory()->for($admin)->create([
            'corrected_calories' => 700,
            'corrected_macros' => ['protein' => 30, 'carbs' => 50, 'fat' => 20],
        ]);

        $this->patch(route('food.update', $entry), [
            'meal_type' => 'dinner',
            'description' => 'Corrected dish',
            'portion' => 'bowl',
        ])->assertRedirect();

        $this->assertNull($entry->fresh()->corrected_calories);
        $this->assertNull($entry->fresh()->corrected_macros);
    }

    public function test_dietitian_latest_reading_respects_90_day_window(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dietitian = User::factory()->create(['role' => User::ROLE_DIETITIAN]);

        BloodSugarReading::factory()->for($admin)->create([
            'value' => 180,
            'logged_at' => now()->subDays(120),
        ]);

        $this->actingAs($dietitian)
            ->get(route('blood-sugar.index'))
            ->assertOk()
            ->assertSee('--', false);
    }
}
