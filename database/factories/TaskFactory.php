<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = array_keys(Task::categories());
        $category = fake()->randomElement($categories);

        return [
            //
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'category' => $category,
            'document_number' => strtoupper(substr($category, 0, 3)).'-'.fake()->unique()->numerify('2026-####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'customer_name' => fake()->company(),
            'service_type' => fake()->randomElement(['Metronet 1Gbps', 'IP VPN', 'Dark Fiber', 'Cloud VPS', 'OLT Upgrade']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => Task::STATUS_PENDING,
            'start_date' => fake()->dateTimeBetween('-1 week', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }
}
