<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\VerificationStatus;
use App\Models\ShowroomVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShowroomVerification
 */
class AdminShowroomVerificationResource extends JsonResource
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
            'startAt' => $this->start_at?->toDateString(),
            'endAt' => $this->end_at?->toDateString(),
            'rejectReason' => $this->reject_reason,
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'reviewAt' => $this->review_at?->toDateString(),
            'createdAt' => $this->created_at?->toDateString(),
        ];
    }
}
