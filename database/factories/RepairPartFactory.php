<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\RepairOrder;
use App\Models\RepairPart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairPart>
 */
class RepairPartFactory extends Factory
{
    protected $model = RepairPart::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        /** @var string $name */
        $name = fake()->randomElement(['Screen', 'Keyboard', 'Battery', 'Motherboard']);

        return [
            'repair_order_id' => RepairOrder::factory(),
            'no' => sprintf('RP%s%s', $year, $sequence),
            'name' => $name,
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => fake()->randomFloat(2, 50, 500),
            'metadata' => ['vendor' => fake()->company()],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
