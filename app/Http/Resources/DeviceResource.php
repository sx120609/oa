<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Device $resource */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $device = $this->resource;

        return [
            'id' => $device->id,
            'asset_tag' => $device->asset_tag,
            'name' => $device->name,
            'category' => $device->category,
            'specification' => $device->specification,
            'status' => $device->status,
            'owner' => $this->whenLoaded('owner', function () use ($device) {
                return [
                    'id' => $device->owner?->id,
                    'name' => $device->owner?->name,
                    'email' => $device->owner?->email,
                ];
            }),
            'location' => $device->location,
            'meta' => $device->meta,
            'purchased_at' => $device->purchased_at?->toISOString(),
            'inbounded_at' => $device->inbounded_at?->toISOString(),
            'assigned_at' => $device->assigned_at?->toISOString(),
            'repaired_at' => $device->repaired_at?->toISOString(),
            'scrapped_at' => $device->scrapped_at?->toISOString(),
            'created_at' => $device->created_at?->toISOString(),
            'updated_at' => $device->updated_at?->toISOString(),
        ];
    }
}
