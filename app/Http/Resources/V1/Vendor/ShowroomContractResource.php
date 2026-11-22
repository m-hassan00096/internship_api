<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\ContractStatus;
use App\Models\ShowroomContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShowroomContract
 */
class ShowroomContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ContractStatus
            ? $this->status
            : ContractStatus::tryFrom((int) $this->status);

        $daysRemaining = $this->end_at ? (int) max(0, now()->diffInDays($this->end_at, false)) : 0;

        return [
            'id' => $this->id,
            'contractNumber' => $this->contract_number,
            'startedAt' => $this->started_at?->toDateString(),
            'endAt' => $this->end_at?->toDateString(),
            'isExpired' => $this->end_at ? $this->isExpired() : false,
            'daysRemaining' => $daysRemaining,
            'amount' => (float) $this->amount,
            'maxRooms' => (int) $this->max_rooms,
            'maxPackages' => (int) $this->max_packages,
            'contractFile' => $this->contract_file,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
