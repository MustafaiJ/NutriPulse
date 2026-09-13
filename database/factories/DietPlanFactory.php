<?php

namespace Database\Factories;

use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DietPlan>
 */
class DietPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory()->dietitian(),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(3, true),
            'target_calories' => fake()->numberBetween(1500, 2500),
            'target_macros' => ['protein' => 120, 'carbs' => 180, 'fat' => 60],
            'active_from' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}