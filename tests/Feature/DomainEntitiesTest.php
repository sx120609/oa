<?php

namespace Tests\Feature;

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
use Database\Seeders\DomainSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainEntitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_support_basic_crud(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'owned_by_project_id' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $asset = Asset::factory()->create([
            'owned_by_project_id' => $project->id,
            'current_user_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'status' => Asset::STATUS_IN_STOCK,
        ]);

        $log = AssetLog::factory()->create([
            'asset_id' => $asset->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'from_status' => Asset::STATUS_DRAFT,
            'to_status' => Asset::STATUS_IN_STOCK,
        ]);

        $purchaseRequest = PurchaseRequest::factory()->create([
            'asset_id' => $asset->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $stockIn = StockIn::factory()->create([
            'asset_id' => $asset->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'status' => StockIn::STATUS_COMPLETED,
        ]);

        $usage = Usage::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'status' => Usage::STATUS_ACTIVE,
        ]);

        $repairOrder = RepairOrder::factory()->create([
            'asset_id' => $asset->id,
            'technician_id' => $user->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'status' => RepairOrder::STATUS_CREATED,
        ]);

        $part = RepairPart::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $worklog = Worklog::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $attachment = Attachment::factory()->create([
            'attachable_type' => Asset::class,
            'attachable_id' => $asset->id,
            'owned_by_project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $asset->update(['status' => Asset::STATUS_IN_USE]);
        $repairOrder->update(['status' => RepairOrder::STATUS_ASSIGNED]);

        $refreshedAsset = $asset->fresh();
        $refreshedOrder = $repairOrder->fresh();
        $this->assertNotNull($refreshedAsset);
        $this->assertNotNull($refreshedOrder);

        $this->assertEquals(Asset::STATUS_IN_USE, $refreshedAsset->status);
        $this->assertEquals(RepairOrder::STATUS_ASSIGNED, $refreshedOrder->status);

        $this->assertNotNull($asset->logs()->firstWhere('id', $log->id));
        $this->assertSame($asset->id, $purchaseRequest->asset_id);
        $this->assertSame($asset->id, $stockIn->asset_id);
        $this->assertSame($asset->id, $usage->asset_id);
        $this->assertSame($repairOrder->id, $part->repair_order_id);
        $this->assertSame($repairOrder->id, $worklog->repair_order_id);
        $firstAttachment = $asset->attachments()->first();
        $this->assertNotNull($firstAttachment);
        $this->assertTrue($attachment->is($firstAttachment));

        $log->delete();
        $purchaseRequest->delete();
        $stockIn->delete();
        $usage->delete();
        $part->delete();
        $worklog->delete();
        $attachment->delete();
        $repairOrder->delete();
        $asset->delete();

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
        $this->assertSoftDeleted('asset_logs', ['id' => $log->id]);
        $this->assertSoftDeleted('purchase_requests', ['id' => $purchaseRequest->id]);
        $this->assertSoftDeleted('stock_ins', ['id' => $stockIn->id]);
        $this->assertSoftDeleted('usages', ['id' => $usage->id]);
        $this->assertSoftDeleted('repair_orders', ['id' => $repairOrder->id]);
        $this->assertSoftDeleted('repair_parts', ['id' => $part->id]);
        $this->assertSoftDeleted('worklogs', ['id' => $worklog->id]);
        $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
    }

    public function test_domain_sample_seeder_populates_data_sets(): void
    {
        $this->seed(DomainSampleSeeder::class);

        $this->assertGreaterThan(0, Project::count());
        $this->assertGreaterThan(0, Asset::count());
        $this->assertGreaterThan(0, AssetLog::count());
        $this->assertGreaterThan(0, PurchaseRequest::count());
        $this->assertGreaterThan(0, StockIn::count());
        $this->assertGreaterThan(0, Usage::count());
        $this->assertGreaterThan(0, RepairOrder::count());
        $this->assertGreaterThan(0, RepairPart::count());
        $this->assertGreaterThan(0, Worklog::count());
        $this->assertGreaterThan(0, Attachment::count());

        $asset = Asset::with(['logs', 'repairOrders.parts', 'repairOrders.worklogs'])->first();
        $this->assertNotNull($asset);

        $this->assertNotEmpty($asset->logs);
        $firstOrder = $asset->repairOrders->first();
        $this->assertNotNull($firstOrder);
        $this->assertNotEmpty($firstOrder->parts);
        $this->assertNotEmpty($firstOrder->worklogs);
    }
}
