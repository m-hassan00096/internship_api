<?php

namespace App\Http\Requests\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadRoomImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserScope::VENDOR;
    }

    protected function prepareForValidation(): void
    {
        $images = $this->file('images') ?? $this->input('images');
        if ($images && ! is_array($images)) {
            $this->files->set('images', [$images]);
            $this->merge(['images' => [$images]]);
        }

        $isPrimary = $this->input('is_primary', $this->input('isPrimary'));
        if (! is_null($isPrimary)) {
            $casted = filter_var($isPrimary, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $finalValue = ! is_null($casted) ? $casted : $isPrimary;
            $this->merge([
                'is_primary' => $finalValue,
                'isPrimary' => $finalValue,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'images' => __('vendor/room.attributes.images'),
            'images.*' => __('vendor/room.attributes.image_single'),
            'is_primary' => __('vendor/room.attributes.is_primary'),
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
