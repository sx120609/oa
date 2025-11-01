<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->numberBetween(1, 999999));
        /** @var string $status */
        $status = fake()->randomElement(['draft', 'active', 'closed']);

        return [
            'no' => sprintf('PRJ%s%s', $year, $sequence),
            'name' => fake()->unique()->company(),
            'status' => $status,
            'description' => fake()->sentence(),
            'metadata' => ['budget' => fake()->numberBetween(10_000, 200_000)],
            'owned_by_project_id' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
