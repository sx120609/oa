<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Project;
use App\Models\RepairOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairOrder>
 */
class RepairOrderFactory extends Factory
{
    protected $model = RepairOrder::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        $reportedAt = now()->subDays(fake()->numberBetween(1, 10));
        /** @var string $status */
        $status = fake()->randomElement([
            RepairOrder::STATUS_CREATED,
            RepairOrder::STATUS_ASSIGNED,
            RepairOrder::STATUS_DIAGNOSED,
        ]);

        return [
            'asset_id' => Asset::factory(),
            'technician_id' => User::factory(),
            'no' => sprintf('RO%s%s', $year, $sequence),
            'status' => $status,
            'reported_at' => $reportedAt,
            'closed_at' => null,
            'summary' => fake()->sentence(),
            'details' => ['issue' => fake()->sentence()],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
