<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Enums\PromotionStatus;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePromotionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawStatus = $this->input('status');
        $statusValue = null;

        if (is_numeric($rawStatus)) {
            $statusValue = (int) $rawStatus;
        } elseif (is_string($rawStatus)) {
            $normalized = strtoupper(trim($rawStatus));
            $matched = match ($normalized) {
                'PENDING', '0' => PromotionStatus::PENDING->value,
                'APPROVED', '1' => PromotionStatus::APPROVED->value,
                'ACTIVE', '2' => PromotionStatus::ACTIVE->value,
                'EXPIRED', '3' => PromotionStatus::EXPIRED->value,
                'REJECTED', '4' => PromotionStatus::REJECTED->value,
                default => null,
            };
            $statusValue = $matched ?? $rawStatus;
        }

        $rejectReason = $this->input('rejectReason', $this->input('reject_reason', $this->input('reason')));

        $this->merge([
            'status' => $statusValue,
            'reject_reason' => $rejectReason,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isRejecting = (int) $this->input('status') === PromotionStatus::REJECTED->value;

        return [
            'status' => ['required', Rule::in([
                PromotionStatus::APPROVED->value,
                PromotionStatus::ACTIVE->value,
                PromotionStatus::EXPIRED->value,
                PromotionStatus::REJECTED->value,
            ])],
            'reject_reason' => [
                $isRejecting ? 'required' : 'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => __('admin/promotion.attributes.status'),
            'reject_reason' => __('admin/promotion.attributes.reject_reason'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => __('admin/promotion.errors.status_invalid'),
            'status.required' => __('admin/promotion.errors.status_required'),
            'reject_reason.required' => __('admin/promotion.errors.reject_reason_required'),
            'reject_reason.min' => __('admin/promotion.errors.reject_reason_min'),
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
