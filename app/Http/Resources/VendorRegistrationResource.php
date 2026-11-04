<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registrationNumber' => $this->registration_number,
            'vendorName' => $this->vendor_name,
            'businessType' => $this->business_type,
            'contactName' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'hasWhatsapp' => (bool) $this->has_whatsapp,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'statusMessage' => $this->status->message(),
            'rejectionReason' => $this->whenNotNull($this->rejection_reason),
            'notes' => $this->whenNotNull($this->notes),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return $this->reviewer ? [
                    'id' => $this->reviewer->id,
                    'email' => $this->reviewer->email,
                ] : null;
            }),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
