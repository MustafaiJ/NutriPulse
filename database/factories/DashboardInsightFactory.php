<?php

namespace Database\Factories;

use App\Models\DashboardInsight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardInsight>
 */
class DashboardInsightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'insight_text' => fake()->sentence(12),
            'generated_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'source' => 'ai',
        ];
    }
}