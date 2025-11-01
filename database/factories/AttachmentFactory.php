<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $year = now()->format('Y');
        $sequence = sprintf('%06d', fake()->unique()->numberBetween(1, 999999));
        $fileName = fake()->lexify('document-????').'.pdf';
        /** @var string $label */
        $label = fake()->word();

        return [
            'no' => sprintf('AT%s%s', $year, $sequence),
            'file_name' => $fileName,
            'disk' => 'local',
            'path' => 'attachments/'.$fileName,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 120_000),
            'attachable_type' => Asset::class,
            'attachable_id' => Asset::factory(),
            'meta' => ['label' => $label],
            'owned_by_project_id' => Project::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
