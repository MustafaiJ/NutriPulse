<?php

namespace Database\Factories;

use App\Models\BloodSugarReading;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodSugarReading>
 */
class BloodSugarReadingFactory extends Factory
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
            'value' => fake()->randomFloat(1, 70, 180),
            'unit' => 'mg/dL',
            'context_tag' => fake()->randomElement(BloodSugarReading::CONTEXTS),
            'logged_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}