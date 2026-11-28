<?php

namespace App\Services\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Enums\PromotionStatus;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use App\Http\Resources\V1\Admin\AdminActivePromotionResource;
use App\Http\Resources\V1\Admin\AdminPromotionPricingResource;
use App\Http\Resources\V1\Admin\AdminPromotionRequestResource;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class AdminPromotionService
{
    /**
     * Get running promotions with KPI metrics, filters, and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getActivePromotions(array $filters = []): array
    {
        $activeCount = Promotion::where('status', PromotionStatus::ACTIVE)->count();

        $query = Promotion::query()
            ->where('status', PromotionStatus::ACTIVE)
            ->with([
                'showroom',
                'promotionType',
                'promotable',
            ]);

        $this->applyFilters($query, $filters);

        $perPage = (int) ($filters['perPage'] ?? 10);
        $paginator = $query->latest('starts_at')->latest('id')->paginate($perPage);

        return [
            'kpis' => [
                'activeCount' => $activeCount,
                'activeCountFormatted' => "{$activeCount} مساحات",
            ],
            'items' => AdminActivePromotionResource::collection($paginator->getCollection()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'ruleNote' => [
                'title' => __('admin/promotion.rules.campaign_v1.title'),
                'note' => __('admin/promotion.rules.campaign_v1.note'),
            ],
        ];
    }

    /**
     * Get incoming pending promotion requests with KPI badge, filters, and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPromotionRequests(array $filters = []): array
    {
        $pendingCount = Promotion::where('status', PromotionStatus::PENDING)->count();

        $query = Promotion::query()
            ->where('status', PromotionStatus::PENDING)
            ->with([
                'showroom',
                'promotionType',
                'promotable',
            ]);

        $this->applyFilters($query, $filters);

        $perPage = (int) ($filters['perPage'] ?? 10);
        $paginator = $query->latest('id')->paginate($perPage);

        return [
            'kpis' => [
                'pendingCount' => $pendingCount,
                'pendingCountFormatted' => "{$pendingCount} طلبات معلقة",
            ],
            'items' => AdminPromotionRequestResource::collection($paginator->getCollection()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'ruleNote' => [
                'title' => __('admin/promotion.rules.instant_start_tab.title'),
                'note' => __('admin/promotion.rules.instant_start_tab.note'),
            ],
        ];
    }

    /**
     * Get promotion details for the modal.
     */
    public function getPromotionDetail(int $id): Promotion
    {
        $promotion = Promotion::with([
            'showroom',
            'promotionType',
            'promotable',
            'reviewer',
            'creator',
        ])->find($id);

        if (! $promotion) {
            throw new HttpResponseException(
                ApiResponse::error(
                    __('admin/promotion.errors.not_found'),
                    HttpStatusCode::NOT_FOUND
                )
            );
        }

        return $promotion;
    }

    /**
     * Update promotion status (Approve & Start instantly, Reject with reason, or Expire).
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePromotionStatus(int $id, array $data, ?User $reviewer = null): Promotion
    {
        $promotion = $this->getPromotionDetail($id);

        $statusValue = (int) $data['status'];
        $now = now();

        if ($statusValue === PromotionStatus::APPROVED->value || $statusValue === PromotionStatus::ACTIVE->value) {
            // Instant Start Rule: start immediately, calculate end date from duration
            $promotion->status = PromotionStatus::ACTIVE;
            $promotion->starts_at = $now;
            $promotion->ends_at = (clone $now)->addDays((int) $promotion->days);
            $promotion->reviewed_by = $reviewer?->id;
            $promotion->reviewed_at = $now;
            $promotion->reject_reason = null;
        } elseif ($statusValue === PromotionStatus::REJECTED->value) {
            $promotion->status = PromotionStatus::REJECTED;
            $promotion->reject_reason = $data['reject_reason'] ?? null;
            $promotion->reviewed_by = $reviewer?->id;
            $promotion->reviewed_at = $now;
        } elseif ($statusValue === PromotionStatus::EXPIRED->value) {
            $promotion->status = PromotionStatus::EXPIRED;
            $promotion->ends_at = $now;
            $promotion->reviewed_by = $reviewer?->id;
            $promotion->reviewed_at = $now;
        }

        $promotion->save();

        return $promotion->fresh([
            'showroom',
            'promotionType',
            'promotable',
            'reviewer',
            'creator',
        ]);
    }

    /**
     * Get pricing settings and configuration.
     *
     * @return array<string, mixed>
     */
    public function getPricingSettings(): array
    {
        $targetPlacements = ['room', 'package', 'banner'];
        $existing = PromotionType::whereIn('placement', $targetPlacements)->get()->keyBy('placement');

        foreach ($targetPlacements as $placement) {
            if (! isset($existing[$placement])) {
                $defaultPrice = match ($placement) {
                    'room' => 200,
                    'package' => 250,
                    'banner' => 500,
                };
                $created = PromotionType::create([
                    'placement' => $placement,
                    'name' => [
                        'ar' => match ($placement) {
                            'room' => 'غرفة',
                            'package' => 'باقة',
                            'banner' => 'Banner',
                        },
                        'en' => match ($placement) {
                            'room' => 'Room',
                            'package' => 'Package',
                            'banner' => 'Banner',
                        },
                    ],
                    'daily_price' => $defaultPrice,
                    'is_active' => true,
                    'updated_at' => Carbon::parse('2026-10-01 10:00:00'),
                ]);
                $existing[$placement] = $created;
            }
        }

        $types = collect($targetPlacements)
            ->map(fn ($p) => $existing[$p] ?? null)
            ->filter();

        $mostRecent = $types->max('updated_at') ?? Carbon::parse('2026-10-01 10:00:00');
        $adminUser = User::where('role', UserScope::ADMIN)->first();
        $adminName = $adminUser?->name ?? 'عبد الرحمن المهدي';

        return [
            'types' => AdminPromotionPricingResource::collection($types),
            'lastUpdated' => __('admin/promotion.pricing_screen.last_updated', [
                'date' => $mostRecent->format('d/m/Y'),
                'admin' => $adminName,
            ]),
            'protectionRule' => [
                'badge' => __('admin/promotion.protection_rule.badge'),
                'title' => __('admin/promotion.protection_rule.title'),
                'description' => __('admin/promotion.protection_rule.description'),
                'example' => [
                    'title' => __('admin/promotion.protection_rule.example_title'),
                    'text' => __('admin/promotion.protection_rule.example_text'),
                ],
            ],
            'channels' => [
                'badge' => __('admin/promotion.channels.badge'),
                'title' => __('admin/promotion.channels.title'),
                'items' => [
                    [
                        'title' => __('admin/promotion.channels.promoted_content.title'),
                        'description' => __('admin/promotion.channels.promoted_content.description'),
                    ],
                    [
                        'title' => __('admin/promotion.channels.banner_advertising.title'),
                        'description' => __('admin/promotion.channels.banner_advertising.description'),
                    ],
                ],
                'note' => __('admin/promotion.channels.note'),
            ],
        ];
    }

    /**
     * Update pricing settings for promotion types.
     *
     * @param  array<int, array{id: int, daily_price: float|numeric}>  $prices
     * @return array<string, mixed>
     */
    public function updatePricing(array $prices, ?User $admin = null): array
    {
        $now = now();

        foreach ($prices as $item) {
            PromotionType::where('id', $item['id'])->update([
                'daily_price' => $item['daily_price'],
                'updated_at' => $now,
            ]);
        }

        return $this->getPricingSettings();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $searchDigits = preg_replace('/[^0-9]/', '', $search);

            $query->where(function (Builder $q) use ($search, $searchDigits): void {
                if ($searchDigits !== '') {
                    $q->where('id', (int) $searchDigits);
                }

                $q->orWhereHas('showroom', function (Builder $sq) use ($search): void {
                    $sq->where('name', 'like', "%{$search}%");
                })->orWhereHasMorph('promotable', [Room::class, Package::class, Showroom::class], function (Builder $pq, string $type) use ($search): void {
                    if ($type === Room::class || $type === Package::class) {
                        $pq
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    } else {
                        $pq->where('name', 'like', "%{$search}%");
                    }
                });
            });
        }

        if (! empty($filters['type'])) {
            $type = trim((string) $filters['type']);
            if (is_numeric($type)) {
                $query->where('promotion_type_id', (int) $type);
            } else {
                $query->where(function (Builder $q) use ($type): void {
                    $q
                        ->where('promotable_type', $type)
                        ->orWhereHas('promotionType', function (Builder $tq) use ($type): void {
                            $tq->where('placement', $type);
                        });
                });
            }
        }
    }
}
