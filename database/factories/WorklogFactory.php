<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Worklog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worklog>
 */
class WorklogFactory extends Factory
{
    protected $model = Worklog::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));

        return [
            'repair_order_id' => RepairOrder::factory(),
            'no' => sprintf('WL%s%s', $year, $sequence),
            'notes' => fake()->paragraph(),
            'worked_at' => now()->subHours(fake()->numberBetween(1, 72)),
            'details' => ['duration_minutes' => fake()->numberBetween(30, 180)],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
