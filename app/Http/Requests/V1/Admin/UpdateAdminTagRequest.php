<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminTagRequest extends FormRequest
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
        $id = $this->route('id') ?? $this->route('tag');

        return [
            'name' => ['nullable', 'array'],
            'name.ar' => ['required_with:name', 'string', 'max:100'],
            'name.en' => ['required_with:name', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('tags', 'slug')->ignore($id)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
