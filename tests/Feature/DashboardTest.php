<?php

namespace Tests\Feature;

use App\Models\BloodSugarReading;
use App\Models\FoodEntry;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_with_stats(): void
    {
        $admin = User::factory()->create();
        FoodEntry::factory()->create([
            'user_id' => $admin->id,
            'ai_calories' => 500,
            'corrected_calories' => null,
            'logged_at' => now(),
        ]);
        BloodSugarReading::factory()->create([
            'user_id' => $admin->id,
            'value' => 95,
            'logged_at' => now(),
        ]);
        Workout::factory()->create([
            'user_id' => $admin->id,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('500');
        $response->assertSee('AI Insight');
    }

    public function test_dietitian_cannot_generate_admin_insight(): void
    {
        $dietitian = User::factory()->dietitian()->create();

        $response = $this->actingAs($dietitian)->post(route('dashboard.insight'));

        $response->assertForbidden();
    }

    public function test_admin_can_generate_insight_on_demand(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('dashboard.insight'));

        $response->assertOk();
        $response->assertJsonStructure(['insight']);
        $this->assertDatabaseHas('dashboard_insights', ['user_id' => $admin->id]);
    }
}
