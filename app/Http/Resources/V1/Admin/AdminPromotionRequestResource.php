<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\PromotionStatus;
use App\Http\Resources\V1\Admin\Traits\ResolvesPromotionContent;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Promotion
 */
class AdminPromotionRequestResource extends JsonResource
{
    use ResolvesPromotionContent;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof PromotionStatus
            ? $this->status
            : PromotionStatus::tryFrom((int) $this->status);

        $content = $this->resolvePromotableContent();

        $typePlacement = $this->promotionType?->placement ?? $this->promotable_type ?? 'room';
        $typeLabel = __('admin/promotion.types.'.$typePlacement) ?? $this->promotionType?->localizedName();

        return [
            'id' => $this->id,
            'type' => $typeLabel,
            'content' => [
                'name' => $content['name'],
                'code' => 'PRM-REQ-'.$this->id,
                'image' => $content['image'],
            ],
            'showroom' => $this->showroom?->name ?? '',
            'duration' => $this->formatDays((int) $this->days),
            'dailyPrice' => number_format((float) $this->daily_price, 0).' '.__('admin/promotion.units.currency'),
            'totalAmount' => number_format((float) $this->total_amount, 0).' '.__('admin/promotion.units.currency'),
            'requestDate' => $this->created_at?->format('d/m/Y'),
            'status' => [
                'value' => $status?->value ?? PromotionStatus::PENDING->value,
                'label' => __('admin/promotion.status.'.($status?->name ?? 'PENDING')),
                'badgeColor' => __('admin/promotion.badge_colors.'.($status?->name ?? 'PENDING')),
            ],
        ];
    }
}
