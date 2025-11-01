<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\PurchaseRequest;
use App\Models\RepairOrder;
use App\Models\RepairPart;
use App\Models\StockIn;
use App\Models\Usage;
use App\Models\User;
use App\Models\Worklog;
use Illuminate\Database\Seeder;

class DomainSampleSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(5)->create();

        $projects = Project::factory()
            ->count(2)
            ->state(function () use ($users) {
                $owner = $users->random();

                return [
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ];
            })
            ->create();

        $assets = Asset::factory()
            ->count(3)
            ->state(function () use ($projects, $users) {
                $project = $projects->random();
                $user = $users->random();

                return [
                    'owned_by_project_id' => $project->id,
                    'current_user_id' => $user->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ];
            })
            ->create();

        foreach ($assets as $asset) {
            $ownerProjectId = $asset->owned_by_project_id;
            $actor = $users->random();

            AssetLog::factory()->create([
                'asset_id' => $asset->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            PurchaseRequest::factory()->create([
                'asset_id' => $asset->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            StockIn::factory()->create([
                'asset_id' => $asset->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            Usage::factory()->create([
                'asset_id' => $asset->id,
                'user_id' => $actor->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $repairOrder = RepairOrder::factory()->create([
                'asset_id' => $asset->id,
                'technician_id' => $actor->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            RepairPart::factory()->count(2)->create([
                'repair_order_id' => $repairOrder->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            Worklog::factory()->create([
                'repair_order_id' => $repairOrder->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            Attachment::factory()->create([
                'attachable_type' => Asset::class,
                'attachable_id' => $asset->id,
                'owned_by_project_id' => $ownerProjectId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }
    }
}
