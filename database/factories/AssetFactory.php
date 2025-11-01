<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        /** @var string $deviceType */
        $deviceType = fake()->randomElement(['Laptop', 'Monitor', 'Phone', 'Printer']);
        /** @var string $status */
        $status = fake()->randomElement([
            Asset::STATUS_DRAFT,
            Asset::STATUS_PURCHASED,
            Asset::STATUS_IN_STOCK,
            Asset::STATUS_IN_USE,
            Asset::STATUS_UNDER_REPAIR,
        ]);
        /** @var string $category */
        $category = fake()->randomElement(['IT', 'Office', 'Lab']);
        /** @var string $cpu */
        $cpu = fake()->randomElement(['i5', 'i7']);
        /** @var string $ram */
        $ram = fake()->randomElement(['8GB', '16GB']);

        return [
            'no' => sprintf('AS%s%s', $year, $sequence),
            'name' => sprintf('%s %s', fake()->word(), $deviceType),
            'asset_tag' => sprintf('TAG-%s-%s', $year, $sequence),
            'status' => $status,
            'category' => $category,
            'serial_number' => strtoupper(fake()->bothify('SN####??')),
            'specification' => ['cpu' => $cpu, 'ram' => $ram],
            'metadata' => ['warranty_expiry' => now()->addYear()->toDateString()],
            'purchased_at' => now()->subDays(fake()->numberBetween(1, 365))->toDateString(),
            'current_user_id' => User::factory(),
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
