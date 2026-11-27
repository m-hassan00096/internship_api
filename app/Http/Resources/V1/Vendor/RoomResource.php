<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\DiscountType;
use App\Enums\RoomAvailabilityStatus;
use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Room
 */
class RoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof RoomStatus
            ? $this->status
            : RoomStatus::tryFrom((int) $this->status);

        $availability = $this->availability_status instanceof RoomAvailabilityStatus
            ? $this->availability_status
            : RoomAvailabilityStatus::tryFrom((int) $this->availability_status);

        $discountType = $this->discount_type instanceof DiscountType
            ? $this->discount_type
            : ($this->discount_type ? DiscountType::tryFrom((int) $this->discount_type) : null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'warranty' => $this->warranty,
            'description' => $this->description,
            'price' => (float) $this->price,
            'discountType' => $discountType ? [
                'value' => $discountType->value,
                'name' => $discountType->name,
                'label' => $discountType->label(),
            ] : null,
            'discountValue' => $this->discount_value ? (float) $this->discount_value : null,
            'discountAmount' => $this->discountAmount(),
            'finalPrice' => $this->finalPrice(),
            'hasDiscount' => $this->hasDiscount(),
            'availabilityStatus' => [
                'value' => $availability?->value,
                'name' => $availability?->name,
                'label' => $availability?->label(),
            ],
            'preparationDays' => $this->preparation_days ? (int) $this->preparation_days : null,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'currentStep' => (int) $this->current_step,
            'isPublished' => $this->isPublished(),
            'publishedAt' => $this->published_at?->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'primaryImage' => new RoomImageResource($this->whenLoaded('primaryImage')),
            'images' => RoomImageResource::collection($this->whenLoaded('images')),
            'components' => RoomComponentResource::collection($this->whenLoaded('components')),
            'imagesCount' => $this->whenCounted('images'),
            'componentsCount' => $this->whenCounted('components'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
