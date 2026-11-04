<?php

namespace App\Http\Requests\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Enums\RegistrationStatus;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreVendorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                Rule::unique('vendor_registrations', 'email')->where(function ($query) {
                    $query->whereNotIn('status', [RegistrationStatus::REJECTED->value]);
                }),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'unique:users,phone',
                Rule::unique('vendor_registrations', 'phone')->where(function ($query) {
                    $query->whereNotIn('status', [RegistrationStatus::REJECTED->value]);
                }),
            ],
            'has_whatsapp' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل أو لديه طلب تسجيل قيد المعالجة.',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل أو لديه طلب تسجيل قيد المعالجة.',
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
