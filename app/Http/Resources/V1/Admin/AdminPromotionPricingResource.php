<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\PromotionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PromotionType
 */
class AdminPromotionPricingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $placement = $this->placement;

        $title = match ($placement) {
            'room' => __('admin/promotion.pricing_screen.room_title'),
            'package' => __('admin/promotion.pricing_screen.package_title'),
            'banner' => __('admin/promotion.pricing_screen.banner_title'),
            default => $this->localizedName(),
        };

        $description = match ($placement) {
            'room' => __('admin/promotion.pricing_screen.room_desc'),
            'package' => __('admin/promotion.pricing_screen.package_desc'),
            'banner' => __('admin/promotion.pricing_screen.banner_desc'),
            default => __('admin/promotion.descriptions.'.$placement) ?? '',
        };

        return [
            'id' => $this->id,
            'placement' => $placement,
            'title' => $title,
            'description' => $description,
            'dailyPrice' => number_format((float) $this->daily_price, 0).' '.__('admin/promotion.units.currency_daily'),
        ];
    }
}
