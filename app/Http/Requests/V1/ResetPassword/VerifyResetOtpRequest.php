<?php

namespace App\Http\Requests\V1\ResetPassword;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyResetOtpRequest extends FormRequest
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
                'otp' => ['required', 'string', 'digits:6'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['prohibited'],
            'otp' => ['required', 'string', 'digits:6'],
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
            'otp' => 'رمز التحقق',
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
