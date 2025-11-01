<?php

namespace App\Http\Requests;

use App\Models\Device;
use App\Models\DeviceTransaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Device::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|Rule|string|array<int, ValidationRule|Rule|string>>
     */
    public function rules(): array
    {
        $existingTransaction = DeviceTransaction::where('no', $this->input('no'))->first();

        return [
            'no' => ['required', 'string', 'max:64', Rule::unique('device_transactions', 'no')->ignore($existingTransaction?->id)],
            'asset_tag' => ['required', 'string', 'max:64', Rule::unique('devices', 'asset_tag')->ignore($existingTransaction?->device_id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'specification' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
