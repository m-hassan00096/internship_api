<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\ContractStatus;
use App\Enums\VerificationStatus;
use App\Models\Showroom;
use App\Models\VendorRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Showroom
 */
class AdminShowroomContractsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contract = $this->activeContract;
        $contractStatus = $contract?->status instanceof ContractStatus
            ? $contract->status
            : ($contract?->status !== null ? ContractStatus::tryFrom((int) $contract->status) : null);

        $maxRooms = (int) ($contract?->max_rooms ?? 0);
        $usedRooms = (int) ($this->rooms_count ?? 0);
        $roomsPercentage = $maxRooms > 0 ? round(($usedRooms / $maxRooms) * 100, 1) : 0.0;

        $maxPackages = (int) ($contract?->max_packages ?? 0);
        $usedPackages = (int) ($this->packages_count ?? 0);
        $packagesPercentage = $maxPackages > 0 ? round(($usedPackages / $maxPackages) * 100, 1) : 0.0;

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

        $baseAmount = (float) ($contract?->amount ?? 0);
        $vatPercentage = 15.0;
        $vatAmount = round($baseAmount * ($vatPercentage / 100), 2);
        $verificationFee = $isVerificationActive ? (float) ($verification->total_amount ?? 0) : 0.0;
        $totalAmount = $baseAmount + $vatAmount + $verificationFee;

        $daysRemaining = $contract?->end_at ? max(0, (int) now()->diffInDays($contract->end_at, false)) : 0;

        $registration = VendorRegistration::where('user_id', $this->user_id)->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'activeContract' => $contract ? [
                'id' => $contract->id,
                'contractNumber' => $contract->contract_number,
                'startedAt' => $contract->started_at?->toDateString(),
                'endAt' => $contract->end_at?->toDateString(),
                'daysRemaining' => $daysRemaining,
                'amount' => $baseAmount,
                'maxRooms' => $maxRooms,
                'maxPackages' => $maxPackages,
                'contractFile' => $contract->contract_file,
                'status' => [
                    'value' => $contractStatus?->value,
                    'label' => $contractStatus?->label(),
                ],
            ] : null,
            'usage' => [
                'rooms' => [
                    'used' => $usedRooms,
                    'max' => $maxRooms,
                    'percentage' => $roomsPercentage,
                ],
                'packages' => [
                    'used' => $usedPackages,
                    'max' => $maxPackages,
                    'percentage' => $packagesPercentage,
                ],
            ],
            'financialSummary' => [
                'baseContractAmount' => $baseAmount,
                'vatPercentage' => $vatPercentage,
                'vatAmount' => $vatAmount,
                'verificationFee' => $verificationFee,
                'totalAmount' => $totalAmount,
                'paymentMethod' => __('admin/showroom.commercial.bank_transfer'),
            ],
            'verification' => [
                'status' => [
                    'value' => $effectiveVerificationStatus->value,
                    'label' => $effectiveVerificationStatus->label(),
                ],
                'month' => $verification ? (int) $verification->month : null,
                'monthlyPrice' => $verification ? (float) $verification->monthly_price : null,
                'totalAmount' => $verification ? (float) $verification->total_amount : null,
                'startAt' => $verification?->start_at?->toDateString(),
                'endAt' => $verification?->end_at?->toDateString(),
            ],
            'contractingParty' => [
                'companyName' => $this->name,
                'contactName' => $registration?->contact_name ?? $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->phone ?? $this->user?->phone,
            ],
        ];
    }
}
