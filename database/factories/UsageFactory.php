<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Project;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usage>
 */
class UsageFactory extends Factory
{
    protected $model = Usage::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        $start = now()->subDays(fake()->numberBetween(1, 20));
        /** @var string $status */
        $status = fake()->randomElement([
            Usage::STATUS_DRAFT,
            Usage::STATUS_ACTIVE,
            Usage::STATUS_RETURNED,
        ]);
        /** @var string $location */
        $location = fake()->city();

        return [
            'asset_id' => Asset::factory(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'no' => sprintf('US%s%s', $year, $sequence),
            'status' => $status,
            'started_at' => $start->toDateString(),
            'ended_at' => $start->copy()->addDays(fake()->numberBetween(1, 5))->toDateString(),
            'details' => ['location' => $location],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
