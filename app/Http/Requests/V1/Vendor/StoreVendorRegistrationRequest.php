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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vendorName' => $this->input('vendorName', $this->input('vendor_name')),
            'businessType' => $this->input('businessType', $this->input('business_type')),
            'contactName' => $this->input('contactName', $this->input('contact_name')),
            'hasWhatsapp' => $this->input('hasWhatsapp', $this->input('has_whatsapp')),
        ]);
    }

    public function rules(): array
    {
        return [
            'vendorName' => ['required', 'string', 'max:255'],
            'businessType' => ['required', 'string', 'max:255'],
            'contactName' => ['required', 'string', 'max:255'],
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
            'hasWhatsapp' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vendorName' => 'اسم المعرض',
            'businessType' => 'نوع النشاط',
            'contactName' => 'اسم جهة الاتصال',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'hasWhatsapp' => 'واتساب',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('vendor/registration.validation.email_unique'),
            'phone.unique' => __('vendor/registration.validation.phone_unique'),
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
