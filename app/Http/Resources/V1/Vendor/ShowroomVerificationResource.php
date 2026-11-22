<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\VerificationStatus;
use App\Models\ShowroomVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShowroomVerification
 */
class ShowroomVerificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof VerificationStatus
            ? $this->status
            : VerificationStatus::tryFrom((int) $this->status);

        $isActive = $status === VerificationStatus::APPROVED
            && $this->start_at
            && $this->end_at
            && now()->between($this->start_at, $this->end_at);

        return [
            'id' => $this->id,
            'month' => (int) $this->month,
            'monthlyPrice' => (float) $this->monthly_price,
            'totalAmount' => (float) $this->total_amount,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'isActive' => $isActive,
            'startAt' => $this->start_at?->toIso8601String(),
            'endAt' => $this->end_at?->toIso8601String(),
            'rejectReason' => $this->reject_reason,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
