<?php

namespace Database\Factories;

use App\Models\FoodEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodEntry>
 */
class FoodEntryFactory extends Factory
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
            'meal_type' => fake()->randomElement(FoodEntry::MEALS),
            'description' => fake()->sentence(4),
            'portion' => fake()->optional()->words(3, true),
            'ai_calories' => fake()->optional()->numberBetween(150, 900),
            'ai_macros' => ['protein' => fake()->numberBetween(5, 50), 'carbs' => fake()->numberBetween(10, 120), 'fat' => fake()->numberBetween(2, 40)],
            'corrected_calories' => null,
            'corrected_macros' => null,
            'logged_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}