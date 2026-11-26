<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\ShowroomStatus;
use App\Models\Showroom;
use App\Models\VendorRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAdminShowroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vendorRegistrationId' => $this->input('vendorRegistrationId', $this->input('vendor_registration_id')),
            'coverImage' => $this->input('coverImage', $this->input('cover_image')),
        ]);

        if ($this->has('contract') && is_array($this->input('contract'))) {
            $contract = $this->input('contract');
            $contract['contractNumber'] = $contract['contractNumber'] ?? $contract['contract_number'] ?? null;
            $contract['startedAt'] = $contract['startedAt'] ?? $contract['started_at'] ?? null;
            $contract['endAt'] = $contract['endAt'] ?? $contract['end_at'] ?? null;
            $contract['maxRooms'] = $contract['maxRooms'] ?? $contract['max_rooms'] ?? null;
            $contract['maxPackages'] = $contract['maxPackages'] ?? $contract['max_packages'] ?? null;
            $contract['contractFile'] = $contract['contractFile'] ?? $contract['contract_file'] ?? null;
            $this->merge(['contract' => $contract]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vendorRegistrationId' => ['required', 'integer', 'exists:vendor_registrations,id'],

            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:showrooms,slug'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'coverImage' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'status' => ['nullable', 'integer', Rule::enum(ShowroomStatus::class)],

            // Main branch details (optional)
            'branch' => ['nullable', 'array'],
            'branch.name' => ['required_with:branch', 'string', 'max:255'],
            'branch.city' => ['nullable', 'string', 'max:100'],
            'branch.address' => ['nullable', 'string', 'max:500'],
            'branch.phone' => ['nullable', 'string', 'max:50'],
            'branch.whatsapp' => ['nullable', 'string', 'max:50'],

            // Contract details
            'contract' => ['required', 'array'],
            'contract.contractNumber' => ['nullable', 'string', 'max:100', 'unique:showrooms_contract,contract_number'],
            'contract.startedAt' => ['required', 'date'],
            'contract.endAt' => ['required', 'date', 'after:contract.startedAt'],
            'contract.amount' => ['required', 'numeric', 'min:0'],
            'contract.maxRooms' => ['required', 'integer', 'min:0'],
            'contract.maxPackages' => ['required', 'integer', 'min:0'],
            'contract.contractFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $registrationId = $this->input('vendorRegistrationId');
                if (! $registrationId) {
                    return;
                }

                $registration = VendorRegistration::find($registrationId);
                if (! $registration) {
                    return;
                }

                if (! $registration->user_id) {
                    $validator->errors()->add('vendorRegistrationId', __('admin/showroom.errors.registration_not_approved'));
                } elseif (Showroom::where('user_id', $registration->user_id)->exists()) {
                    $validator->errors()->add('vendorRegistrationId', __('admin/showroom.errors.user_has_showroom'));
                }
            },
        ];
    }
}
