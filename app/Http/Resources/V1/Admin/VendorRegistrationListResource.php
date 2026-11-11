<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\RegistrationStatus;
use App\Models\VendorRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VendorRegistration
 */
class VendorRegistrationListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof RegistrationStatus
            ? $this->status
            : RegistrationStatus::tryFrom((int) $this->status);

        return [
            'id' => $this->id,
            'registrationNumber' => $this->registration_number,
            'vendorName' => $this->vendor_name,
            'businessTypeName' => $this->whenLoaded('businessType', function (): ?string {
                return $this->businessType?->localizedName();
            }),
            'contactName' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
