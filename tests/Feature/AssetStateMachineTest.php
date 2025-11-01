<?php

namespace Tests\Feature;

use App\Domain\Services\AssetService;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use SM\SMException;
use Tests\TestCase;

class AssetStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_to_user_happy_path_creates_audit_and_notification(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'asset.status_transitioned'
                    && ($context['from'] ?? null) === Asset::STATUS_IN_STOCK
                    && ($context['to'] ?? null) === Asset::STATUS_IN_USE;
            });

        $actor = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();
        $asset = Asset::factory()->create([
            'status' => Asset::STATUS_IN_STOCK,
            'current_user_id' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->actingAs($actor);

        $service = app(AssetService::class);
        $service->assignToUser($asset, $target, $actor, 'req-123');

        $asset->refresh();

        $this->assertSame(Asset::STATUS_IN_USE, $asset->status);
        $this->assertSame($target->id, $asset->current_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'asset.status_transition',
        ]);

        $audit = AuditLog::where('auditable_id', $asset->id)->latest()->first();
        $this->assertNotNull($audit);
        $this->assertSame('req-123', $audit->payload['request_id'] ?? null);
        $this->assertSame($target->id, $audit->payload['target_user_id'] ?? null);

        // expectation configured above via shouldReceive
    }

    public function test_assign_to_user_rejected_for_invalid_state(): void
    {
        $actor = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();
        $asset = Asset::factory()->create([
            'status' => Asset::STATUS_DRAFT,
            'current_user_id' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->actingAs($actor);

        $service = app(AssetService::class);

        $this->expectException(SMException::class);
        $service->assignToUser($asset, $target, $actor);
    }
}
