<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\StoreReviewReplyRequest;
use App\Http\Requests\V1\Vendor\UpdateReviewReplyRequest;
use App\Http\Resources\V1\Vendor\ReviewReplyResource;
use App\Http\Resources\V1\Vendor\ReviewResource;
use App\Services\V1\Vendor\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $service
    ) {}

    /**
     * استعراض قائمة تقييمات المعرض
     */
    public function index(Request $request): JsonResponse
    {
        $reviews = $this->service->listReviews(
            $request->user(),
            $request->query()
        );

        return ApiResponse::success([
            'items' => ReviewResource::collection($reviews->getCollection()),
            'pagination' => [
                'currentPage' => $reviews->currentPage(),
                'lastPage' => $reviews->lastPage(),
                'perPage' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * إحصائيات تقييمات المعرض ومتوسط النجوم
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->service->getStats($request->user());

        return ApiResponse::success($stats);
    }

    /**
     * تفاصيل تقييم محدد مع الرد
     */
    public function show(Request $request, int $review): JsonResponse
    {
        $reviewModel = $this->service->getReview($request->user(), $review);

        return ApiResponse::success(
            new ReviewResource($reviewModel)
        );
    }

    /**
     * إضافة رد من المعرض على التقييم
     */
    public function storeReply(StoreReviewReplyRequest $request, int $review): JsonResponse
    {
        $reply = $this->service->replyToReview(
            $request->user(),
            $review,
            $request->validated()
        );

        return ApiResponse::success(
            new ReviewReplyResource($reply),
            __('vendor/review.messages.replied'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * تعديل رد المعرض على التقييم
     */
    public function updateReply(UpdateReviewReplyRequest $request, int $review): JsonResponse
    {
        $reply = $this->service->updateReply(
            $request->user(),
            $review,
            $request->validated()
        );

        return ApiResponse::success(
            new ReviewReplyResource($reply),
            __('vendor/review.messages.reply_updated')
        );
    }

    /**
     * حذف رد المعرض
     */
    public function deleteReply(Request $request, int $review): JsonResponse
    {
        $this->service->deleteReply($request->user(), $review);

        return ApiResponse::success(
            null,
            __('vendor/review.messages.reply_deleted')
        );
    }
}
