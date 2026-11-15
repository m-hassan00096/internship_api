<?php

use App\Enums\UserScope;
use App\Http\Controllers\Api\V1\Admin\LoginController;
use App\Http\Controllers\Api\V1\Admin\LogoutController;
use App\Http\Controllers\Api\V1\Admin\VendorRegistrationController;
use App\Http\Controllers\Api\V1\ResetPassword\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', LoginController::class)
            ->name('login');

        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
            ->defaults('scope', UserScope::ADMIN->value)
            ->name('admin.auth.forgot-password');

        Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])
            ->defaults('scope', UserScope::ADMIN->value)
            ->name('admin.auth.verify-otp');

        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
            ->defaults('scope', UserScope::ADMIN->value)
            ->name('admin.auth.reset-password');

        Route::post('/logout', LogoutController::class)
            ->middleware('auth:web')
            ->name('logout');
    });

    Route::prefix('vendor-registrations')
        ->middleware(['auth:web', 'scope:admin'])
        ->name('admin.vendor-registrations.')
        ->group(function (): void {
            Route::get('/', [VendorRegistrationController::class, 'index'])
                ->name('index');

            Route::get('/{id}', [VendorRegistrationController::class, 'show'])
                ->name('show');

            Route::patch('/{id}/status', [VendorRegistrationController::class, 'changeStatus'])
                ->name('change-status');

            Route::post('/{id}/approve', [VendorRegistrationController::class, 'approve'])
                ->name('approve');

            Route::post('/{id}/reject', [VendorRegistrationController::class, 'reject'])
                ->name('reject');
        });
});
