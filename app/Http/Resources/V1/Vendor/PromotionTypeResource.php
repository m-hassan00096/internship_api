<?php

namespace App\Http\Resources\V1\Vendor;

use App\Models\PromotionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PromotionType
 */
class PromotionTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localizedName(),
            'placement' => $this->placement,
            'placementLabel' => __('vendor/promotion.placements.'.$this->placement),
            'dailyPrice' => (float) $this->daily_price,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
