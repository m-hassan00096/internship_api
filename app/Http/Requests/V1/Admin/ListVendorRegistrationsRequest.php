<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Enums\RegistrationStatus;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ListVendorRegistrationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserScope::ADMIN;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('per_page') && ! $this->has('perPage')) {
            $this->merge([
                'perPage' => $this->input('per_page'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'integer', Rule::in(array_column(RegistrationStatus::cases(), 'value'))],
            'search' => ['nullable', 'string', 'max:255'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
            'search' => 'البحث',
            'perPage' => 'عدد العناصر',
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
