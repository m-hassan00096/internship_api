<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\PromotionStatus;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Promotion
 */
class PromotionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof PromotionStatus
            ? $this->status
            : PromotionStatus::tryFrom((int) $this->status);

        return [
            'id' => $this->id,
            'promotableType' => $this->promotable_type,
            'promotableId' => (int) $this->promotable_id,
            'promotable' => $this->whenLoaded('promotable', function () {
                if ($this->promotable instanceof Room) {
                    $primaryImage = $this->promotable->primaryImage;

                    return [
                        'type' => 'room',
                        'id' => $this->promotable->id,
                        'name' => $this->promotable->name,
                        'sku' => $this->promotable->sku,
                        'price' => (float) $this->promotable->price,
                        'finalPrice' => $this->promotable->finalPrice(),
                        'primaryImage' => $primaryImage ? [
                            'id' => $primaryImage->id,
                            'url' => str_starts_with($primaryImage->image_path, 'http')
                                ? $primaryImage->image_path
                                : Storage::url($primaryImage->image_path),
                        ] : null,
                    ];
                }

                if ($this->promotable instanceof Package) {
                    return [
                        'type' => 'package',
                        'id' => $this->promotable->id,
                        'name' => $this->promotable->name,
                        'sku' => $this->promotable->sku,
                        'price' => ! is_null($this->promotable->price) ? (float) $this->promotable->price : null,
                        'image' => $this->promotable->image_path ? (
                            str_starts_with($this->promotable->image_path, 'http')
                                ? $this->promotable->image_path
                                : Storage::url($this->promotable->image_path)
                        ) : null,
                    ];
                }

                return null;
            }),
            'promotionType' => new PromotionTypeResource($this->whenLoaded('promotionType')),
            'days' => (int) $this->days,
            'dailyPrice' => (float) $this->daily_price,
            'totalAmount' => (float) $this->total_amount,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'isActive' => $this->isActive(),
            'startsAt' => $this->starts_at?->toIso8601String(),
            'endsAt' => $this->ends_at?->toIso8601String(),
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
            'rejectReason' => $this->reject_reason,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
