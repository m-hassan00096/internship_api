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
class AdminActivePromotionResource extends JsonResource
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

        $remainingDays = 0;
        if ($this->ends_at && $this->ends_at->isFuture()) {
            $remainingDays = max(0, (int) ceil(now()->diffInDays($this->ends_at, false)));
        }

        $typePlacement = $this->promotionType?->placement ?? $this->promotable_type ?? 'room';
        $typeLabel = __('admin/promotion.types.'.$typePlacement) ?? $this->promotionType?->localizedName();

        return [
            'id' => $this->id,
            'type' => $typeLabel,
            'content' => [
                'name' => $content['name'],
                'code' => 'PRM-'.$this->id,
                'image' => $content['image'],
            ],
            'showroom' => $this->showroom?->name ?? '',
            'startsAt' => $this->starts_at?->format('d/m/Y'),
            'endsAt' => $this->ends_at?->format('d/m/Y'),
            'remainingDays' => $this->formatDays($remainingDays),
            'dailyPrice' => number_format((float) $this->daily_price, 0).' '.__('admin/promotion.units.currency'),
            'totalAmount' => number_format((float) $this->total_amount, 0).' '.__('admin/promotion.units.currency'),
            'status' => [
                'value' => $status?->value ?? PromotionStatus::ACTIVE->value,
                'label' => __('admin/promotion.status.'.($status?->name ?? 'ACTIVE')),
                'badgeColor' => __('admin/promotion.badge_colors.'.($status?->name ?? 'ACTIVE')),
            ],
        ];
    }
}
