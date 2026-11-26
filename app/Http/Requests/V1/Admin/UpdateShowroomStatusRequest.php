<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\ShowroomStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShowroomStatusRequest extends FormRequest
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
        return [
            'status' => ['required', 'integer', Rule::enum(ShowroomStatus::class)],
        ];
    }
}
