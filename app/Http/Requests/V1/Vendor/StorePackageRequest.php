<?php

namespace App\Http\Requests\V1\Vendor;

use App\Enums\PackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // دعم استقبال rooms كمصفوفة أرقام [1, 2] أو مصفوفة كائنات [{'id': 1, 'sort_order': 1}]
        if ($this->has('rooms') && is_array($this->input('rooms'))) {
            $rooms = $this->input('rooms');
            if (! empty($rooms) && (is_numeric($rooms[0]) || is_string($rooms[0]))) {
                $formatted = [];
                foreach ($rooms as $index => $roomId) {
                    $formatted[] = [
                        'id' => (int) $roomId,
                        'sort_order' => $index + 1,
                    ];
                }
                $this->merge(['rooms' => $formatted]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'status' => ['nullable', Rule::enum(PackageStatus::class)],
            'currentStep' => ['nullable', 'integer', 'min:1', 'max:10'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.id' => ['required', 'integer', 'exists:rooms,id'],
            'rooms.*.sortOrder' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('vendor/package.attributes.name'),
            'sku' => __('vendor/package.attributes.sku'),
            'description' => __('vendor/package.attributes.description'),
            'price' => __('vendor/package.attributes.price'),
            'image' => __('vendor/package.attributes.image'),
            'status' => __('vendor/package.attributes.status'),
            'rooms' => __('vendor/package.attributes.rooms'),
        ];
    }
}
