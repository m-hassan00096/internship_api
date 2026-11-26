<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageShowroomVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'monthlyPrice' => $this->input('monthlyPrice', $this->input('monthly_price')),
            'totalAmount' => $this->input('totalAmount', $this->input('total_amount')),
            'startAt' => $this->input('startAt', $this->input('start_at')),
            'endAt' => $this->input('endAt', $this->input('end_at')),
            'rejectReason' => $this->input('rejectReason', $this->input('reject_reason')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:120'],
            'monthlyPrice' => ['required', 'numeric', 'min:0'],
            'totalAmount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'integer', Rule::enum(VerificationStatus::class)],
            'startAt' => ['nullable', 'date'],
            'endAt' => ['nullable', 'date', 'after:startAt'],
            'rejectReason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
