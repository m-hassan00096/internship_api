<?php

namespace App\Http\Controllers\Api\V1\ResetPassword;

use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ResetPassword\ForgotPasswordRequest;
use App\Http\Requests\V1\ResetPassword\ResetPasswordRequest;
use App\Http\Requests\V1\ResetPassword\VerifyResetOtpRequest;
use App\Services\V1\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {}

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $scope = $this->resolveScope($request);
        $target = $this->resolveTarget($data);

        $channel = $this->passwordResetService->sendOtp(
            $target['identifier'],
            $scope,
            $target['channel']
        );

        $message = $channel === 'whatsapp'
            ? __('auth.password_reset.otp_sent_whatsapp')
            : __('auth.password_reset.otp_sent_email');

        return ApiResponse::success(null, $message);
    }

    public function verifyOtp(VerifyResetOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $scope = $this->resolveScope($request);
        $target = $this->resolveTarget($data);

        $resetToken = $this->passwordResetService->verifyOtp(
            $target['identifier'],
            $data['otp'],
            $scope,
            $target['channel']
        );

        return ApiResponse::success([
            'resetToken' => $resetToken,
        ], __('auth.password_reset.otp_verified'));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $scope = $this->resolveScope($request);
        $target = $this->resolveTarget($data);

        $this->passwordResetService->resetPassword(
            $target['identifier'],
            $data['resetToken'],
            $data['password'],
            $scope,
            $target['channel']
        );

        return ApiResponse::success(null, __('auth.password_reset.password_updated'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{identifier: string, channel: 'email'|'whatsapp'}
     */
    private function resolveTarget(array $data): array
    {
        if (! empty($data['phone'])) {
            return [
                'identifier' => (string) $data['phone'],
                'channel' => 'whatsapp',
            ];
        }

        return [
            'identifier' => (string) $data['email'],
            'channel' => 'email',
        ];
    }

    private function resolveScope(Request $request): UserScope
    {
        return UserScope::from((int) $request->route('scope'));
    }
}
