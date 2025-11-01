<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetLog>
 */
class AssetLogFactory extends Factory
{
    protected $model = AssetLog::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        /** @var string $from */
        $from = fake()->randomElement([
            Asset::STATUS_DRAFT,
            Asset::STATUS_PURCHASED,
            Asset::STATUS_IN_STOCK,
        ]);
        /** @var string $to */
        $to = fake()->randomElement([
            Asset::STATUS_PURCHASED,
            Asset::STATUS_IN_STOCK,
            Asset::STATUS_IN_USE,
            Asset::STATUS_UNDER_REPAIR,
        ]);
        /** @var string $event */
        $event = fake()->randomElement(['created', 'status_changed', 'note']);
        /** @var string $source */
        $source = fake()->randomElement(['api', 'system']);

        return [
            'asset_id' => Asset::factory(),
            'no' => sprintf('LG%s%s', $year, $sequence),
            'event' => $event,
            'from_status' => $from,
            'to_status' => $to,
            'changes' => ['from' => $from, 'to' => $to],
            'source' => $source,
            'request_id' => fake()->uuid(),
            'description' => fake()->sentence(),
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
