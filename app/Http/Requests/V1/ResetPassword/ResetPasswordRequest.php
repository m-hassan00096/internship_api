<?php

namespace App\Http\Requests\V1\ResetPassword;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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

        $identifierRules = $scope === UserScope::CLIENT
            ? [
                'email' => ['nullable', 'required_without:phone', 'prohibits:phone', 'string', 'email', 'max:255'],
                'phone' => ['nullable', 'required_without:email', 'prohibits:email', 'string', 'max:20'],
            ]
            : [
                'email' => ['required', 'string', 'email', 'max:255'],
                'phone' => ['prohibited'],
            ];

        return [
            ...$identifierRules,
            'resetToken' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                Password::min(8)->numbers()->symbols(),
                'confirmed:passwordConfirmation',
            ],
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
            'resetToken' => 'رمز إعادة التعيين',
            'password' => 'كلمة المرور',
            'passwordConfirmation' => 'تأكيد كلمة المرور',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => __('auth.password_reset.password_confirmed'),
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
