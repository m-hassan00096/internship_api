<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Models\Showroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Showroom
 */
class AdminShowroomListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ShowroomStatus
            ? $this->status
            : ShowroomStatus::tryFrom((int) $this->status);

        $contract = $this->activeContract;
        $contractStatus = $contract?->status instanceof ContractStatus
            ? $contract->status
            : ($contract?->status !== null ? ContractStatus::tryFrom((int) $contract->status) : null);

        $maxRooms = (int) ($contract?->max_rooms ?? 0);
        $usedRooms = (int) ($this->rooms_count ?? 0);

        $maxPackages = (int) ($contract?->max_packages ?? 0);
        $usedPackages = (int) ($this->packages_count ?? 0);

        $daysRemaining = $contract?->end_at ? max(0, (int) now()->diffInDays($contract->end_at, false)) : 0;
        $isExpiringSoon = $contract && $contract->end_at && $daysRemaining <= 30 && now()->diffInDays($contract->end_at, false) >= 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => $this->logo,
            'isVerified' => $this->relationLoaded('activeVerification')
                ? (bool) $this->activeVerification
                : false,
            'businessType' => $this->whenLoaded('businessType', function (): ?array {
                if (! $this->businessType) {
                    return null;
                }

                return [
                    'id' => $this->businessType->id,
                    'name' => $this->businessType->localizedName(),
                ];
            }),
            'contactPerson' => $this->whenLoaded('user', function (): ?array {
                if (! $this->user) {
                    return null;
                }

                return [
                    'name' => $this->user->name,
                    'phone' => $this->user->phone,
                    'email' => $this->user->email,
                ];
            }),
            'status' => [
                'value' => $status?->value,
                'label' => $status?->label(),
            ],
            'contract' => $contract ? [
                'status' => [
                    'value' => $contractStatus?->value,
                    'label' => $contractStatus?->label(),
                ],
                'endAt' => $contract->end_at?->toDateString(),
                'daysRemaining' => $daysRemaining,
                'isExpiringSoon' => (bool) $isExpiringSoon,
            ] : null,
            'roomsQuota' => [
                'used' => $usedRooms,
                'max' => $maxRooms,
            ],
            'packagesQuota' => [
                'used' => $usedPackages,
                'max' => $maxPackages,
            ],
        ];
    }
}
