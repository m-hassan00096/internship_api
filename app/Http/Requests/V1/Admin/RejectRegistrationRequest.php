<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RejectRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserScope::ADMIN;
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('reason', $this->input('rejectionReason', $this->input('rejection_reason')));

        if ($reason !== null) {
            $this->merge([
                'reason' => $reason,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => 'سبب الرفض',
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
