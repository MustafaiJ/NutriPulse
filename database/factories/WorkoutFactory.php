<?php

namespace Database\Factories;

use App\Models\Workout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Workout::TYPES);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'exercise' => $type === 'strength'
                ? fake()->randomElement(['Bench Press', 'Squat', 'Deadlift', 'Overhead Press'])
                : null,
            'details_json' => $type === 'strength'
                ? ['sets' => fake()->numberBetween(3, 5), 'reps' => fake()->numberBetween(5, 12), 'weight_kg' => fake()->randomFloat(1, 20, 120)]
                : ['distance_km' => fake()->randomFloat(1, 1, 12)],
            'duration_minutes' => fake()->numberBetween(15, 90),
            'logged_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}