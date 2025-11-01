<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Project;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockIn>
 */
class StockInFactory extends Factory
{
    protected $model = StockIn::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        /** @var string $status */
        $status = fake()->randomElement([
            StockIn::STATUS_PENDING,
            StockIn::STATUS_COMPLETED,
        ]);
        /** @var string $location */
        $location = fake()->randomElement(['Main Warehouse', 'Backup Storage']);

        return [
            'asset_id' => Asset::factory(),
            'no' => sprintf('SI%s%s', $year, $sequence),
            'status' => $status,
            'location' => $location,
            'received_at' => now()->subDays(fake()->numberBetween(1, 15))->toDateString(),
            'details' => ['batch' => fake()->numerify('BATCH-###')],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
