<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\ShowroomStatus;
use App\Models\Showroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Showroom
 */
class ShowroomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ShowroomStatus
            ? $this->status
            : ShowroomStatus::tryFrom((int) $this->status);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'logo' => $this->logo,
            'coverImage' => $this->cover_image,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'businessType' => $this->whenLoaded('businessType', function (): ?array {
                if (! $this->businessType) {
                    return null;
                }

                return [
                    'id' => $this->businessType->id,
                    'name' => $this->businessType->localizedName(),
                ];
            }),
            'isVerified' => $this->isVerified(),
            'activeContract' => new ShowroomContractResource($this->whenLoaded('activeContract')),
            'activeVerification' => new ShowroomVerificationResource($this->whenLoaded('activeVerification')),
            'branches' => ShowroomBranchResource::collection($this->whenLoaded('branches')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
