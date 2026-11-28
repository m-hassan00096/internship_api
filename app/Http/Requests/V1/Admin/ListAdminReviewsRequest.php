<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminReviewsRequest extends FormRequest
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
            'targetType' => $this->input('targetType', $this->input('target_type', $this->input('type'))),
            'rating' => $this->input('rating'),
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
            'targetType' => ['nullable', 'string', Rule::in(['room', 'showroom'])],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'sortBy' => ['nullable', 'string', Rule::in(['latest', 'oldest', 'rating_high', 'rating_low'])],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
