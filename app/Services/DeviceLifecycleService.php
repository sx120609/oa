<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeviceLifecycleService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array{no:string,asset_tag:string,name:string,category?:string|null,specification?:string|null,meta?:array<string, mixed>|null}  $payload
     */
    public function purchase(array $payload, User $actor): Device
    {
        return DB::transaction(function () use ($payload, $actor) {
            $transaction = DeviceTransaction::firstOrCreate(
                ['no' => $payload['no']],
                [
                    'user_id' => $actor->id,
                    'type' => 'purchase',
                    'payload' => $payload,
                ],
            );

            if (! $transaction->wasRecentlyCreated) {
                return $transaction->device()->firstOrFail();
            }

            /** @var Device $device */
            $device = Device::create([
                'asset_tag' => $payload['asset_tag'],
                'name' => $payload['name'],
                'category' => $payload['category'] ?? null,
                'specification' => $payload['specification'] ?? null,
                'status' => 'purchased',
                'meta' => $payload['meta'] ?? null,
                'purchased_at' => now(),
            ]);

            $transaction->device()->associate($device);
            $transaction->status_after = $device->status;
            $transaction->save();

            $this->auditLogger->log(
                user: $actor,
                auditable: $device,
                action: 'device.purchased',
                description: 'Device purchased',
                payload: $payload,
            );

            return $device;
        });
    }

    /**
     * @param  array{no:string,asset_tag:string,location?:string|null,inbounded_at?:string|null}  $payload
     */
    public function inbound(array $payload, User $actor): Device
    {
        return DB::transaction(function () use ($payload, $actor) {
            $transaction = DeviceTransaction::firstOrCreate(
                ['no' => $payload['no']],
                [
                    'user_id' => $actor->id,
                    'type' => 'inbound',
                    'payload' => $payload,
                ],
            );

            $device = $this->resolveDeviceByTransaction($transaction, $payload['asset_tag']);

            if ($transaction->wasRecentlyCreated) {
                $previous = $device->status;

                $device->fill([
                    'status' => 'in_stock',
                    'location' => $payload['location'] ?? $device->location,
                    'inbounded_at' => $this->parseNullableDate($payload['inbounded_at'] ?? null) ?? now(),
                ])->save();

                $transaction->status_before = $previous;
                $transaction->status_after = $device->status;
                $transaction->device()->associate($device);
                $transaction->save();

                $this->auditLogger->log(
                    user: $actor,
                    auditable: $device,
                    action: 'device.inbounded',
                    description: 'Device inbounded to storage',
                    payload: $payload,
                );
            }

            return $device->refresh();
        });
    }

    /**
     * @param  array{no:string,asset_tag:string,target_user_id:int,location?:string|null}  $payload
     */
    public function assign(array $payload, User $actor): Device
    {
        return DB::transaction(function () use ($payload, $actor) {
            $transaction = DeviceTransaction::firstOrCreate(
                ['no' => $payload['no']],
                [
                    'user_id' => $actor->id,
                    'type' => 'assign',
                    'payload' => $payload,
                ],
            );

            $device = $this->resolveDeviceByTransaction($transaction, $payload['asset_tag']);

            if ($transaction->wasRecentlyCreated) {
                $previous = $device->status;

                $device->fill([
                    'status' => 'in_use',
                    'owner_id' => $payload['target_user_id'],
                    'location' => $payload['location'] ?? $device->location,
                    'assigned_at' => now(),
                ])->save();

                $transaction->status_before = $previous;
                $transaction->status_after = $device->status;
                $transaction->device()->associate($device);
                $transaction->save();

                $this->auditLogger->log(
                    user: $actor,
                    auditable: $device,
                    action: 'device.assigned',
                    description: 'Device assigned or transferred to user',
                    payload: $payload,
                );
            }

            return $device->refresh()->load('owner');
        });
    }

    /**
     * @param  array{no:string,asset_tag:string,notes?:string|null}  $payload
     */
    public function repair(array $payload, User $actor): Device
    {
        return DB::transaction(function () use ($payload, $actor) {
            $transaction = DeviceTransaction::firstOrCreate(
                ['no' => $payload['no']],
                [
                    'user_id' => $actor->id,
                    'type' => 'repair',
                    'payload' => $payload,
                ],
            );

            $device = $this->resolveDeviceByTransaction($transaction, $payload['asset_tag']);

            if ($transaction->wasRecentlyCreated) {
                $previous = $device->status;

                $device->fill([
                    'status' => 'under_repair',
                    'repaired_at' => now(),
                ])->save();

                $transaction->status_before = $previous;
                $transaction->status_after = $device->status;
                $transaction->device()->associate($device);
                $transaction->save();

                $this->auditLogger->log(
                    user: $actor,
                    auditable: $device,
                    action: 'device.repairing',
                    description: 'Device sent for repair',
                    payload: $payload,
                );
            }

            return $device->refresh();
        });
    }

    /**
     * @param  array{no:string,asset_tag:string,reason:string,scrapped_at?:string|null}  $payload
     */
    public function scrap(array $payload, User $actor): Device
    {
        return DB::transaction(function () use ($payload, $actor) {
            $transaction = DeviceTransaction::firstOrCreate(
                ['no' => $payload['no']],
                [
                    'user_id' => $actor->id,
                    'type' => 'scrap',
                    'payload' => $payload,
                ],
            );

            $device = $this->resolveDeviceByTransaction($transaction, $payload['asset_tag']);

            if ($transaction->wasRecentlyCreated) {
                $previous = $device->status;

                $device->fill([
                    'status' => 'scrapped',
                    'scrapped_at' => $this->parseNullableDate($payload['scrapped_at'] ?? null) ?? now(),
                ])->save();

                $transaction->status_before = $previous;
                $transaction->status_after = $device->status;
                $transaction->device()->associate($device);
                $transaction->save();

                $this->auditLogger->log(
                    user: $actor,
                    auditable: $device,
                    action: 'device.scrapped',
                    description: 'Device scrapped',
                    payload: $payload,
                );
            }

            return $device->refresh();
        });
    }

    private function resolveDeviceByTransaction(DeviceTransaction $transaction, string $assetTag): Device
    {
        if ($transaction->device instanceof Device) {
            return $transaction->device;
        }

        /** @var Device $device */
        $device = Device::query()->where('asset_tag', $assetTag)->firstOrFail();

        return $device;
    }

    private function parseNullableDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value);
    }
}
