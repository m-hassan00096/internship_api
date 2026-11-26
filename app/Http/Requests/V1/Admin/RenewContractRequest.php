<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RenewContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contractNumber' => $this->input('contractNumber', $this->input('contract_number')),
            'startedAt' => $this->input('startedAt', $this->input('started_at')),
            'endAt' => $this->input('endAt', $this->input('end_at')),
            'maxRooms' => $this->input('maxRooms', $this->input('max_rooms', $this->input('max_Rooms'))),
            'maxPackages' => $this->input('maxPackages', $this->input('max_packages')),
            'contractFile' => $this->input('contractFile', $this->input('contract_file')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contractNumber' => ['nullable', 'string', 'max:100', 'unique:showrooms_contract,contract_number'],
            'startedAt' => ['required', 'date'],
            'endAt' => ['required', 'date', 'after:startedAt'],
            'amount' => ['required', 'numeric', 'min:0'],
            'maxRooms' => ['required', 'integer', 'min:0'],
            'maxPackages' => ['required', 'integer', 'min:0'],
            'contractFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
