<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'perPage' => $this->input('perPage', $this->input('per_page', 10)),
            'sortBy' => $this->input('sortBy', $this->input('sort_by', 'sort_order_asc')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:all,active,inactive,1,0'],
            'sortBy' => ['nullable', 'string', 'in:sort_order_asc,sort_order_desc,name_asc,name_desc,latest'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
