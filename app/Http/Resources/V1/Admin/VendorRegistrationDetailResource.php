<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\UserActive;
use App\Models\VendorRegistration;
use App\Models\VendorRegistrationHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VendorRegistration
 */
class VendorRegistrationDetailResource extends JsonResource
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

        $latestHistory = $this->relationLoaded('histories')
            ? $this->histories->first()
            : null;

        $rejectedHistory = ($status === RegistrationStatus::REJECTED && $this->relationLoaded('histories'))
            ? $this->histories->first(fn (VendorRegistrationHistory $history): bool => $history->status === RegistrationStatus::REJECTED)
            : null;

        $reviewer = ($latestHistory && $latestHistory->relationLoaded('changedBy'))
            ? $latestHistory->changedBy
            : null;

        return [
            'id' => $this->id,
            'registrationNumber' => $this->registration_number,
            'vendorName' => $this->vendor_name,
            'businessType' => $this->whenLoaded('businessType', function (): ?array {
                if (! $this->businessType) {
                    return null;
                }

                return [
                    'id' => $this->businessType->id,
                    'name' => $this->businessType->localizedName(),
                ];
            }),
            'contactName' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'hasWhatsapp' => (bool) $this->has_whatsapp,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
                'message' => $status?->message(),
            ],
            'rejectionReason' => $rejectedHistory?->notes,
            'latestNotes' => $latestHistory?->notes,
            'reviewer' => $reviewer ? [
                'id' => $reviewer->id,
                'email' => $reviewer->email,
            ] : null,
            'vendorAccount' => $this->whenLoaded('user', function (): ?array {
                if (! $this->user) {
                    return null;
                }

                $userActive = $this->user->user_active instanceof UserActive
                    ? $this->user->user_active
                    : UserActive::tryFrom((int) $this->user->user_active);

                return [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'userActive' => [
                        'value' => $userActive?->value,
                        'name' => $userActive?->name,
                        'label' => $userActive?->label(),
                    ],
                ];
            }),
            'timeline' => VendorRegistrationHistoryResource::collection($this->whenLoaded('histories')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
