<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListShowroomPackagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'showroomId' => $this->input('showroomId', $this->input('showroom_id', $this->input('id'))),
            'perPage' => $this->input('perPage', $this->input('per_page', 10)),
            'sortBy' => $this->input('sortBy', $this->input('sort_by', 'latest')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'showroomId' => ['nullable', 'integer', 'exists:showrooms,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sortBy' => ['nullable', 'string', 'in:latest,oldest,price_asc,price_desc,name_asc,name_desc'],
        ];
    }
}
