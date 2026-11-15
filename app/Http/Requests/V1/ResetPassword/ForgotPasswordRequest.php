<?php

namespace App\Http\Requests\V1\ResetPassword;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scope = UserScope::tryFrom((int) $this->route('scope'));

        if ($scope === UserScope::CLIENT) {
            return [
                'email' => ['nullable', 'required_without:phone', 'prohibits:phone', 'string', 'email', 'max:255'],
                'phone' => ['nullable', 'required_without:email', 'prohibits:email', 'string', 'max:20'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
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
