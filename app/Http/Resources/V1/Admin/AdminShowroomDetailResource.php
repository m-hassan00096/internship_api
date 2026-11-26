<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Enums\UserActive;
use App\Enums\VerificationStatus;
use App\Models\Showroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Showroom
 */
class AdminShowroomDetailResource extends JsonResource
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

        $verification = $this->activeVerification;
        $verificationStatus = $verification?->status instanceof VerificationStatus
            ? $verification->status
            : ($verification?->status !== null ? VerificationStatus::tryFrom((int) $verification->status) : null);

        $isVerificationActive = $verification
            && $verificationStatus === VerificationStatus::APPROVED
            && $verification->start_at
            && $verification->end_at
            && now()->between($verification->start_at, $verification->end_at);

        $effectiveVerificationStatus = $isVerificationActive
            ? VerificationStatus::APPROVED
            : ($verificationStatus ?? VerificationStatus::UNVERIFIED);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'logo' => $this->logo,
            'phone' => $this->phone,
            'status' => [
                'value' => $status?->value,
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
            'vendorAccount' => $this->whenLoaded('user', function (): ?array {
                if (! $this->user) {
                    return null;
                }

                $userStatus = $this->user->user_active instanceof UserActive
                    ? $this->user->user_active
                    : UserActive::tryFrom((int) $this->user->user_active);

                return [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'status' => [
                        'value' => $userStatus?->value,
                        'label' => $userStatus?->label(),
                    ],
                ];
            }),
            'content' => [
                'rooms' => [
                    'used' => $usedRooms,
                    'max' => $maxRooms,
                ],
                'packages' => [
                    'used' => $usedPackages,
                    'max' => $maxPackages,
                ],
            ],
            'activeContract' => $contract ? [
                'contractNumber' => $contract->contract_number,
                'startedAt' => $contract->started_at?->toDateString(),
                'endAt' => $contract->end_at?->toDateString(),
                'amount' => (float) $contract->amount,
                'maxRooms' => $maxRooms,
                'maxPackages' => $maxPackages,
                'status' => [
                    'value' => $contractStatus?->value,
                    'label' => $contractStatus?->label(),
                ],
            ] : null,
            'activeVerification' => [
                'status' => [
                    'value' => $effectiveVerificationStatus->value,
                    'name' => $effectiveVerificationStatus->name,
                    'label' => $effectiveVerificationStatus->label(),
                ],
                'month' => $verification ? (int) $verification->month : null,
                'startAt' => $verification?->start_at?->toDateString(),
                'endAt' => $verification?->end_at?->toDateString(),
            ],
        ];
    }
}
