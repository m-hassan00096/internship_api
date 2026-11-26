<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListAdminUsersRequest;
use App\Http\Requests\V1\Admin\UpdateUserStatusRequest;
use App\Http\Resources\V1\Admin\AdminUserDetailResource;
use App\Services\V1\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private AdminUserService $userService
    ) {}

    /**
     * Display paginated client users with KPIs and filters.
     */
    public function index(ListAdminUsersRequest $request): JsonResponse
    {
        $data = $this->userService->getUsersList($request->validated());

        return ApiResponse::success($data);
    }

    /**
     * Display the specified client user details.
     */
    public function show(int|string $id): JsonResponse
    {
        $user = $this->userService->getUserDetail($id);

        return ApiResponse::success(new AdminUserDetailResource($user));
    }

    /**
     * Update the client user status (block/unblock/activate).
     */
    public function updateStatus(UpdateUserStatusRequest $request, int|string $id): JsonResponse
    {
        $user = $this->userService->updateUserStatus($id, $request->validated('status'));

        return ApiResponse::success(
            new AdminUserDetailResource($user),
            __('admin/user.messages.status_updated')
        );
    }
}
