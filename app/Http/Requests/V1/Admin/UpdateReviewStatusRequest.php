<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Enums\ReviewStatus;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateReviewStatusRequest extends FormRequest
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
                'PENDING', '0' => ReviewStatus::PENDING->value,
                'APPROVED', '1' => ReviewStatus::APPROVED->value,
                'REJECTED', '2' => ReviewStatus::REJECTED->value,
                'HIDDEN', '3' => ReviewStatus::HIDDEN->value,
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
        $isRejecting = (int) $this->input('status') === ReviewStatus::REJECTED->value;

        return [
            'status' => ['required', Rule::in([
                ReviewStatus::PENDING->value,
                ReviewStatus::APPROVED->value,
                ReviewStatus::REJECTED->value,
                ReviewStatus::HIDDEN->value,
            ])],
            'reject_reason' => [
                $isRejecting ? 'required' : 'nullable',
                'string',
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
            'status' => __('admin/review.attributes.status'),
            'reject_reason' => __('admin/review.attributes.reject_reason'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => __('admin/review.errors.status_invalid'),
            'status.required' => __('admin/review.errors.status_required'),
            'reject_reason.required' => __('admin/review.errors.reject_reason_required'),
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
