<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_lifecycle_flow_is_idempotent_and_updates_statuses(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $assignee = User::factory()->create();

        $this->actingAs($admin);

        $purchasePayload = [
            'no' => 'PO-2024-0001',
            'asset_tag' => 'ASSET-0001',
            'name' => 'Laptop',
            'category' => 'IT',
            'specification' => '16GB RAM',
        ];

        $purchaseResponse = $this->postJson('/api/devices/purchase', $purchasePayload);
        $purchaseResponse->assertSuccessful()->assertJsonPath('data.asset_tag', 'ASSET-0001');
        $this->assertDatabaseHas('devices', [
            'asset_tag' => 'ASSET-0001',
            'status' => 'purchased',
        ]);

        // Idempotency check for purchase
        $this->postJson('/api/devices/purchase', $purchasePayload)->assertSuccessful();
        $this->assertSame(1, Device::count());

        $inboundPayload = [
            'no' => 'IN-2024-0001',
            'asset_tag' => 'ASSET-0001',
            'location' => 'Main Warehouse',
        ];

        $this->postJson('/api/devices/inbound', $inboundPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'in_stock');

        // Idempotency check for inbound
        $this->postJson('/api/devices/inbound', $inboundPayload)->assertSuccessful();

        $assignPayload = [
            'no' => 'AL-2024-0001',
            'asset_tag' => 'ASSET-0001',
            'target_user_id' => $assignee->id,
            'location' => 'HQ 5F',
        ];

        $this->postJson('/api/devices/assign', $assignPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.owner.id', $assignee->id)
            ->assertJsonPath('data.status', 'in_use');

        // Idempotency check for assign
        $this->postJson('/api/devices/assign', $assignPayload)->assertSuccessful();

        $repairPayload = [
            'no' => 'RP-2024-0001',
            'asset_tag' => 'ASSET-0001',
            'notes' => 'Screen replacement',
        ];

        $this->postJson('/api/devices/repair', $repairPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'under_repair');

        $this->postJson('/api/devices/repair', $repairPayload)->assertSuccessful();

        $scrapPayload = [
            'no' => 'SC-2024-0001',
            'asset_tag' => 'ASSET-0001',
            'reason' => 'Damaged beyond repair',
        ];

        $this->postJson('/api/devices/scrap', $scrapPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'scrapped');

        $this->postJson('/api/devices/scrap', $scrapPayload)->assertSuccessful();

        $device = Device::where('asset_tag', 'ASSET-0001')->firstOrFail();
        $this->assertEquals('scrapped', $device->status);

        $this->assertSame(5, DeviceTransaction::count());
    }
}
