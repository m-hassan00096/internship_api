<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'perPage' => $this->input('perPage', $this->input('per_page', 10)),
            'sortBy' => $this->input('sortBy', $this->input('sort_by', 'latest')),
            'authMethod' => $this->input('authMethod', $this->input('auth_method')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable'],
            'authMethod' => ['nullable', 'string', 'in:phone_password,google,apple'],
            'sortBy' => ['nullable', 'string', 'in:latest,oldest,name_asc,name_desc'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
