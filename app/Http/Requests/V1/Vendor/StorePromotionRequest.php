<?php

namespace App\Http\Requests\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserScope::VENDOR;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'promotable_type' => $this->input('promotableType', $this->input('promotable_type', 'room')),
            'promotable_id' => $this->input('promotableId', $this->input('promotable_id')),
            'promotion_type_id' => $this->input('promotionTypeId', $this->input('promotion_type_id')),
            'days' => $this->input('days'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'promotable_type' => ['required', 'string', Rule::in(['room', 'package'])],
            'promotable_id' => ['required', 'integer'],
            'promotion_type_id' => [
                'required',
                'integer',
                Rule::exists('promotion_types', 'id')->where('is_active', true),
            ],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'promotable_type' => __('vendor/promotion.attributes.promotable_type'),
            'promotable_id' => __('vendor/promotion.attributes.promotable_id'),
            'promotion_type_id' => __('vendor/promotion.attributes.promotion_type_id'),
            'days' => __('vendor/promotion.attributes.days'),
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
