<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApproveRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserScope::ADMIN;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notes' => 'الملاحظات',
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
