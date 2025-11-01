<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignDeviceRequest;
use App\Http\Requests\InboundDeviceRequest;
use App\Http\Requests\PurchaseDeviceRequest;
use App\Http\Requests\RepairDeviceRequest;
use App\Http\Requests\ScrapDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\User;
use App\Services\DeviceLifecycleService;

class DeviceLifecycleController extends Controller
{
    public function purchase(PurchaseDeviceRequest $request, DeviceLifecycleService $service): DeviceResource
    {
        /** @var array{no:string,asset_tag:string,name:string,category?:string|null,specification?:string|null,meta?:array<string, mixed>|null} $payload */
        $payload = $request->validated();
        $user = $this->resolveUser($request->user());

        $device = $service->purchase($payload, $user);

        return new DeviceResource($device);
    }

    public function inbound(InboundDeviceRequest $request, DeviceLifecycleService $service): DeviceResource
    {
        /** @var array{no:string,asset_tag:string,location?:string|null,inbounded_at?:string|null} $payload */
        $payload = $request->validated();
        $user = $this->resolveUser($request->user());

        $device = $service->inbound($payload, $user);

        return new DeviceResource($device);
    }

    public function assign(AssignDeviceRequest $request, DeviceLifecycleService $service): DeviceResource
    {
        /** @var array{no:string,asset_tag:string,target_user_id:int,location?:string|null} $payload */
        $payload = $request->validated();
        $user = $this->resolveUser($request->user());

        $device = $service->assign($payload, $user);

        return new DeviceResource($device);
    }

    public function repair(RepairDeviceRequest $request, DeviceLifecycleService $service): DeviceResource
    {
        /** @var array{no:string,asset_tag:string,notes?:string|null} $payload */
        $payload = $request->validated();
        $user = $this->resolveUser($request->user());

        $device = $service->repair($payload, $user);

        return new DeviceResource($device);
    }

    public function scrap(ScrapDeviceRequest $request, DeviceLifecycleService $service): DeviceResource
    {
        /** @var array{no:string,asset_tag:string,reason:string,scrapped_at?:string|null} $payload */
        $payload = $request->validated();
        $user = $this->resolveUser($request->user());

        $device = $service->scrap($payload, $user);

        return new DeviceResource($device);
    }

    private function resolveUser(?User $user): User
    {
        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
