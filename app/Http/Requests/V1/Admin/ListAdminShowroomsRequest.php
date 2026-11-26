<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminShowroomsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'businessTypeId' => $this->input('businessTypeId', $this->input('business_type_id')),
            'contractStatus' => $this->input('contractStatus', $this->input('contract_status')),
            'perPage' => $this->input('perPage', $this->input('per_page')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', Rule::enum(ShowroomStatus::class)],
            'businessTypeId' => ['nullable', 'integer', 'exists:business_types,id'],
            'contractStatus' => ['nullable', 'integer', Rule::enum(ContractStatus::class)],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
