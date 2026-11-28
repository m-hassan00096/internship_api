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
class AdminPromotionDetailResource extends JsonResource
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
        $typeLabel = __('admin/promotion.types_detailed.'.$this->promotable_type)
            ?? __('admin/promotion.types_detailed.'.$typePlacement)
            ?? $this->promotionType?->localizedName();

        $projectedStart = $this->starts_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $projectedEnd = $this->ends_at?->format('d/m/Y') ?? now()->addDays($this->days)->format('d/m/Y');

        $operationalText = match ($status) {
            PromotionStatus::ACTIVE => __('admin/promotion.rules.instant_start.active_template', [
                'start_date' => $projectedStart,
                'end_date' => $projectedEnd,
            ]),
            PromotionStatus::REJECTED => __('admin/promotion.rules.instant_start.rejected_template'),
            PromotionStatus::EXPIRED => __('admin/promotion.rules.instant_start.expired_template', [
                'end_date' => $projectedEnd,
            ]),
            default => __('admin/promotion.rules.instant_start.pending_template', [
                'start_date' => $projectedStart,
                'days' => $this->days,
                'end_date' => $projectedEnd,
            ]),
        };

        $showroomCode = 'SHW-2026-'.str_pad((string) ($this->showroom?->id ?? 1), 4, '0', STR_PAD_LEFT);

        return [
            'id' => $this->id,
            'refCode' => '#PRM-REQ-'.$this->id,
            'requestDate' => $this->created_at?->format('d/m/Y - h:i A'),
            'status' => [
                'value' => $status?->value ?? PromotionStatus::PENDING->value,
                'label' => __('admin/promotion.status.'.($status?->name ?? 'PENDING')),
                'badgeColor' => __('admin/promotion.badge_colors.'.($status?->name ?? 'PENDING')),
            ],
            'targetEntity' => [
                'type' => $this->promotable_type,
                'typeLabel' => $typeLabel,
                'id' => (int) $this->promotable_id,
                'name' => $content['name'],
                'sku' => $content['sku'],
                'image' => $content['image'],
            ],
            'showroom' => [
                'id' => $this->showroom?->id,
                'name' => $this->showroom?->name,
                'code' => $showroomCode,
                'logo' => $this->formatImageUrl($this->showroom?->logo),
                'initials' => $this->resolveShowroomInitials($this->showroom?->name),
            ],
            'pricing' => [
                'duration' => $this->formatDays((int) $this->days),
                'dailyPrice' => number_format((float) $this->daily_price, 0).' '.__('admin/promotion.units.currency_daily'),
                'totalAmount' => number_format((float) $this->total_amount, 0).' '.__('admin/promotion.units.currency'),
                'durationNote' => __('admin/promotion.pricing_notes.duration'),
                'dailyPriceNote' => __('admin/promotion.pricing_notes.daily_price'),
                'totalAmountNote' => __('admin/promotion.pricing_notes.total_amount'),
                'priceLockNote' => __('admin/promotion.pricing_notes.price_lock'),
            ],
            'operationalRule' => [
                'title' => __('admin/promotion.rules.instant_start.title'),
                'text' => $operationalText,
            ],
            'moderation' => [
                'reviewedBy' => $this->reviewer ? [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                    'email' => $this->reviewer->email,
                ] : null,
                'reviewedAt' => $this->reviewed_at?->format('d/m/Y - h:i A'),
                'rejectReason' => $this->reject_reason,
            ],
        ];
    }
}
