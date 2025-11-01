<?php

namespace App\Http\Requests;

use App\Models\Device;
use App\Models\DeviceTransaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepairDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Device::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|Rule|string|array<int, ValidationRule|Rule|string>>
     */
    public function rules(): array
    {
        $existingTransaction = DeviceTransaction::where('no', $this->input('no'))->first();

        return [
            'no' => ['required', 'string', 'max:64', Rule::unique('device_transactions', 'no')->ignore($existingTransaction?->id)],
            'asset_tag' => ['required', 'string', 'exists:devices,asset_tag'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
