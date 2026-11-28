<?php

namespace App\Services\V1\Admin;

use App\Enums\ReviewStatus;
use App\Http\Resources\V1\Admin\AdminReviewListResource;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminReviewService
{
    /**
     * Get paginated reviews list with platform KPIs and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReviewsList(array $filters = []): array
    {
        // 1. Calculate platform KPIs (matching Figma screen top badges: 42 Reviews, 12 pending, 3 rejected)
        $totalReviews = Review::count();
        $pendingReviews = Review::where('status', ReviewStatus::PENDING)->count();
        $rejectedReviews = Review::where('status', ReviewStatus::REJECTED)->count();

        // 2. Build filtered query
        $query = Review::query()->with([
            'user',
            'showroom',
            'reviewable',
            'reply.user',
            'reviewer',
        ]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('showroom', function (Builder $sq) use ($search): void {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHasMorph('reviewable', [Room::class, Showroom::class], function (Builder $rq, string $type) use ($search): void {
                        if ($type === Room::class) {
                            $rq->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        } else {
                            $rq->where('name', 'like', "%{$search}%");
                        }
                    });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $status = $filters['status'];
            $statusEnum = null;

            if (is_numeric($status)) {
                $statusEnum = ReviewStatus::tryFrom((int) $status);
            } elseif (is_string($status)) {
                $statusEnum = match (strtoupper(trim($status))) {
                    'PENDING', '0' => ReviewStatus::PENDING,
                    'APPROVED', '1' => ReviewStatus::APPROVED,
                    'REJECTED', '2' => ReviewStatus::REJECTED,
                    'HIDDEN', '3' => ReviewStatus::HIDDEN,
                    default => null,
                };
            }

            if ($statusEnum !== null) {
                $query->where('status', $statusEnum);
            }
        }

        if (! empty($filters['targetType']) && $filters['targetType'] !== 'all') {
            $query->where('reviewable_type', $filters['targetType']);
        }

        if (! empty($filters['rating']) && $filters['rating'] !== 'all') {
            $query->where('rating', (int) $filters['rating']);
        }

        match ($filters['sortBy'] ?? 'latest') {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'rating_high' => $query->orderByDesc('rating')->orderByDesc('id'),
            'rating_low' => $query->orderBy('rating')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $perPage = (int) ($filters['perPage'] ?? 10);
        $paginator = $query->paginate($perPage);

        return [
            'kpis' => [
                'total' => $totalReviews,
                'pending' => $pendingReviews,
                'rejected' => $rejectedReviews,
            ],
            'items' => AdminReviewListResource::collection($paginator->items()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Get specific review details with all relations.
     */
    public function getReviewDetail(int|string $id): Review
    {
        return Review::with([
            'user',
            'showroom',
            'reviewable',
            'reply.user',
            'reviewer',
        ])->findOrFail($id);
    }

    /**
     * Update review status and record moderation info.
     */
    public function updateReviewStatus(
        int|string $id,
        ReviewStatus|int|string $status,
        ?string $rejectReason,
        User $admin
    ): Review {
        $review = $this->getReviewDetail($id);

        $statusEnum = $status instanceof ReviewStatus
            ? $status
            : (is_numeric($status) ? ReviewStatus::from((int) $status) : match (strtoupper(trim($status))) {
                'PENDING' => ReviewStatus::PENDING,
                'APPROVED' => ReviewStatus::APPROVED,
                'REJECTED' => ReviewStatus::REJECTED,
                'HIDDEN' => ReviewStatus::HIDDEN,
                default => ReviewStatus::from((int) $status),
            });

        $review->update([
            'status' => $statusEnum,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'reject_reason' => $statusEnum === ReviewStatus::REJECTED ? $rejectReason : null,
        ]);

        return $review->fresh([
            'user',
            'showroom',
            'reviewable',
            'reply.user',
            'reviewer',
        ]);
    }
}
