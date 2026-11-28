<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListAdminReviewsRequest;
use App\Http\Requests\V1\Admin\UpdateReviewStatusRequest;
use App\Http\Resources\V1\Admin\AdminReviewDetailResource;
use App\Services\V1\Admin\AdminReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(
        private AdminReviewService $reviewService
    ) {}

    /**
     * Display a listing of reviews with platform KPIs, filters, and pagination.
     */
    public function index(ListAdminReviewsRequest $request): JsonResponse
    {
        $data = $this->reviewService->getReviewsList($request->validated());

        return ApiResponse::success($data, __('admin/review.messages.fetched'));
    }

    /**
     * Display the specified review details.
     */
    public function show(int $id): JsonResponse
    {
        $review = $this->reviewService->getReviewDetail($id);

        return ApiResponse::success(
            new AdminReviewDetailResource($review),
            __('admin/review.messages.detail_fetched')
        );
    }

    /**
     * Update review status (approve, hide, reject, pending).
     */
    public function updateStatus(UpdateReviewStatusRequest $request, int $id): JsonResponse
    {
        $review = $this->reviewService->updateReviewStatus(
            $id,
            $request->validated('status'),
            $request->validated('reject_reason'),
            $request->user()
        );

        return ApiResponse::success(
            new AdminReviewDetailResource($review),
            __('admin/review.messages.status_updated')
        );
    }
}
