<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\ContractStatus;
use App\Models\ShowroomContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShowroomContract
 */
class AdminShowroomContractHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ContractStatus
            ? $this->status
            : ($this->status !== null ? ContractStatus::tryFrom((int) $this->status) : null);

        $daysRemaining = $this->end_at ? max(0, (int) now()->diffInDays($this->end_at, false)) : 0;
        $durationMonths = $this->started_at && $this->end_at ? (int) round($this->started_at->diffInMonths($this->end_at)) : null;
        $isCurrent = $this->showroom?->activeContract?->id === $this->id;
        $baseAmount = (float) $this->amount;
        $additionalServicesFee = $isCurrent && $this->showroom?->activeVerification
            ? (float) ($this->showroom->activeVerification->total_amount ?? 0)
            : 0.0;
        $totalAgreementAmount = $baseAmount + $additionalServicesFee;

        return [
            'id' => $this->id,
            'contractNumber' => $this->contract_number,
            'startedAt' => $this->started_at?->toDateString(),
            'endAt' => $this->end_at?->toDateString(),
            'durationMonths' => $durationMonths,
            'isCurrent' => $isCurrent,
            'baseAmount' => $baseAmount,
            'additionalServicesFee' => $additionalServicesFee,
            'totalAgreementAmount' => $totalAgreementAmount,
            'daysRemaining' => $daysRemaining,
            'isExpired' => $this->end_at ? $this->isExpired() : false,
            'amount' => $baseAmount,
            'maxRooms' => (int) $this->max_rooms,
            'maxPackages' => (int) $this->max_packages,
            'contractFile' => $this->contract_file,
            'status' => [
                'value' => $status?->value,
                'label' => $status?->label(),
            ],
            'createdAt' => $this->created_at?->toDateString(),
        ];
    }
}
