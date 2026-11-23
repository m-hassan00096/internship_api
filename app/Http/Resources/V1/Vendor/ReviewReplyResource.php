<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\ReviewStatus;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReviewReply
 */
class ReviewReplyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ReviewStatus
            ? $this->status
            : ReviewStatus::tryFrom((int) $this->status);

        return [
            'id' => $this->id,
            'reply' => $this->reply,
            'status' => [
                'value' => $status?->value,
                'label' => $status?->label(),
            ],
            'rejectReason' => $this->when($status === ReviewStatus::REJECTED, $this->reject_reason),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
