<?php

namespace Database\Factories;

use App\Models\TravelPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelPeriod>
 */
class TravelPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-30 days', '+30 days');

        return [
            'user_id' => User::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+'.fake()->numberBetween(1, 10).' days')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}