<?php

namespace App\Http\Requests\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRoomComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserScope::VENDOR;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.name' => ['required_with:attributes', 'string', 'max:255'],
            'attributes.*.value' => ['required_with:attributes', 'string', 'max:255'],
            'attributes.*.unit' => ['nullable', 'string', 'max:50'],
            'attributes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('vendor/room.attributes.component_name'),
            'quantity' => __('vendor/room.attributes.quantity'),
            'image' => __('vendor/room.attributes.component_image'),
            'description' => __('vendor/room.attributes.description'),
            'sort_order' => __('vendor/room.attributes.sort_order'),
            'attributes' => __('vendor/room.attributes.attributes'),
            'attributes.*.name' => __('vendor/room.attributes.attribute_name'),
            'attributes.*.value' => __('vendor/room.attributes.attribute_value'),
            'attributes.*.unit' => __('vendor/room.attributes.attribute_unit'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                $validator->errors()->first(),
                HttpStatusCode::VALIDATION_ERROR
            )
        );
    }
}
