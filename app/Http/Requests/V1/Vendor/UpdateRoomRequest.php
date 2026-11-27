<?php

namespace App\Http\Requests\V1\Vendor;

use App\Enums\DiscountType;
use App\Enums\HttpStatusCode;
use App\Enums\RoomAvailabilityStatus;
use App\Enums\RoomStatus;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class UpdateRoomRequest extends FormRequest
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
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', new Enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'availability_status' => ['sometimes', 'required', new Enum(RoomAvailabilityStatus::class)],
            'preparation_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', new Enum(RoomStatus::class)],
            'current_step' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => __('vendor/room.attributes.category_id'),
            'name' => __('vendor/room.attributes.name'),
            'sku' => __('vendor/room.attributes.sku'),
            'warranty' => __('vendor/room.attributes.warranty'),
            'description' => __('vendor/room.attributes.description'),
            'price' => __('vendor/room.attributes.price'),
            'discount_type' => __('vendor/room.attributes.discount_type'),
            'discount_value' => __('vendor/room.attributes.discount_value'),
            'availability_status' => __('vendor/room.attributes.availability_status'),
            'preparation_days' => __('vendor/room.attributes.preparation_days'),
            'status' => __('vendor/room.attributes.status'),
            'current_step' => __('vendor/room.attributes.current_step'),
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
