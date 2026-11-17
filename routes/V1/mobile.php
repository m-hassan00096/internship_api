<?php

use App\Enums\UserScope;
use App\Http\Controllers\Api\V1\Mobile\GoogleLoginMobileController;
use App\Http\Controllers\Api\V1\Mobile\LoginMobileController;
use App\Http\Controllers\Api\V1\Mobile\LogoutMobileController;
use App\Http\Controllers\Api\V1\ResetPassword\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', LoginMobileController::class);
        Route::post('/google', GoogleLoginMobileController::class)->name('mobile.auth.google');

        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
            ->defaults('scope', UserScope::CLIENT->value)
            ->name('mobile.auth.forgot-password');

        Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])
            ->defaults('scope', UserScope::CLIENT->value)
            ->name('mobile.auth.verify-otp');

        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
            ->defaults('scope', UserScope::CLIENT->value)
            ->name('mobile.auth.reset-password');

        Route::post('/logout', LogoutMobileController::class)
            ->middleware('auth:sanctum');
    });
});
