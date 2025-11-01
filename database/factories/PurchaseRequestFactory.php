<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Project;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        /** @var string $status */
        $status = fake()->randomElement([
            PurchaseRequest::STATUS_DRAFT,
            PurchaseRequest::STATUS_SUBMITTED,
            PurchaseRequest::STATUS_APPROVED,
        ]);

        return [
            'asset_id' => Asset::factory(),
            'no' => sprintf('PR%s%s', $year, $sequence),
            'status' => $status,
            'title' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 1000, 10000),
            'requested_at' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'details' => ['items' => fake()->words(3)],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
