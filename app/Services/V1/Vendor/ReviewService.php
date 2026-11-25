<?php

namespace App\Services\V1\Vendor;

use App\Enums\ReviewStatus;
use App\Exceptions\ReviewActionException;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(
        private ShowroomService $showroomService
    ) {}

    /**
     * جلب تقييمات المعرض مع الفلترة والترقيم
     *
     * @param  array<string, mixed>  $filters
     */
    public function listReviews(User $user, array $filters = []): LengthAwarePaginator
    {
        $showroom = $this->showroomService->getShowroom($user);

        $query = $showroom->reviews()
            ->with(['user', 'reviewable', 'reply.user']);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        if (! empty($filters['reviewable_type'])) {
            $query->where('reviewable_type', $filters['reviewable_type']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search): void {
                        $uq->where('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * جلب إحصائيات تقييمات المعرض
     *
     * @return array<string, mixed>
     */
    public function getStats(User $user): array
    {
        $showroom = $this->showroomService->getShowroom($user);

        $baseQuery = $showroom->reviews()->approved();

        $totalReviews = (clone $baseQuery)->count();
        $averageRating = $totalReviews > 0 ? round((float) ((clone $baseQuery)->avg('rating') ?? 0), 1) : 0.0;

        $ratingDistribution = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = (clone $baseQuery)->where('rating', $star)->count();
            $ratingDistribution[$star] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0.0,
            ];
        }

        $pendingReviewsCount = $showroom->reviews()->pending()->count();

        return [
            'totalReviews' => $totalReviews,
            'averageRating' => (float) $averageRating,
            'pendingReviews' => $pendingReviewsCount,
            'distribution' => $ratingDistribution,
        ];
    }

    /**
     * جلب تقييم محدد مع التحقق من ملكيته للمعرض
     */
    public function getReview(User $user, int $id): Review
    {
        $showroom = $this->showroomService->getShowroom($user);

        $review = $showroom->reviews()
            ->with(['user', 'reviewable', 'reply.user'])
            ->find($id);

        if (! $review) {
            throw new ReviewActionException(__('vendor/review.errors.not_found'));
        }

        return $review;
    }

    /**
     * الرد على تقييم
     *
     * @param  array<string, mixed>  $data
     */
    public function replyToReview(User $user, int $reviewId, array $data): ReviewReply
    {
        $review = $this->getReview($user, $reviewId);

        if ($review->reply()->exists()) {
            throw new ReviewActionException(__('vendor/review.errors.already_replied'));
        }

        return DB::transaction(function () use ($review, $user, $data) {
            return ReviewReply::create([
                'reviews_id' => $review->id,
                'user_id' => $user->id,
                'reply' => $data['reply'],
                'status' => ReviewStatus::PENDING,
            ]);
        });
    }

    /**
     * تعديل رد المعرض
     *
     * @param  array<string, mixed>  $data
     */
    public function updateReply(User $user, int $reviewId, array $data): ReviewReply
    {
        $review = $this->getReview($user, $reviewId);

        $reply = $review->reply;

        if (! $reply) {
            throw new ReviewActionException(__('vendor/review.errors.reply_not_found'));
        }

        $reply->update([
            'reply' => $data['reply'],
            'status' => ReviewStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
        ]);

        return $reply;
    }

    /**
     * حذف رد المعرض
     */
    public function deleteReply(User $user, int $reviewId): void
    {
        $review = $this->getReview($user, $reviewId);

        $reply = $review->reply;

        if (! $reply) {
            throw new ReviewActionException(__('vendor/review.errors.reply_not_found'));
        }

        $reply->delete();
    }
}
