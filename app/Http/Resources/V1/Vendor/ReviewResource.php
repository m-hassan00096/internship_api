<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
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
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'status' => [
                'value' => $status?->value,
                'label' => $status?->label(),
            ],
            'rejectReason' => $this->when($status === ReviewStatus::REJECTED, $this->reject_reason),
            'customer' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'phone' => $this->user->phone,
                    'avatar' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
                ];
            }),
            'reviewable' => $this->whenLoaded('reviewable', function () {
                if ($this->reviewable instanceof Room) {
                    $primaryImage = $this->reviewable->primaryImage;

                    return [
                        'type' => 'room',
                        'id' => $this->reviewable->id,
                        'name' => $this->reviewable->name,
                        'primaryImage' => $primaryImage ? (
                            str_starts_with($primaryImage->image_path, 'http')
                                ? $primaryImage->image_path
                                : Storage::url($primaryImage->image_path)
                        ) : null,
                    ];
                }

                if ($this->reviewable instanceof Showroom) {
                    return [
                        'type' => 'showroom',
                        'id' => $this->reviewable->id,
                        'name' => $this->reviewable->name,
                        'logo' => $this->reviewable->logo ? Storage::url($this->reviewable->logo) : null,
                    ];
                }

                return null;
            }),
            'reply' => new ReviewReplyResource($this->whenLoaded('reply')),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
