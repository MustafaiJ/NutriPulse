<?php

namespace Database\Factories;

use App\Models\FoodEntryComment;
use App\Models\FoodEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodEntryComment>
 */
class FoodEntryCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_entry_id' => FoodEntry::factory(),
            'user_id' => User::factory()->dietitian(),
            'comment' => fake()->sentence(8),
        ];
    }
}