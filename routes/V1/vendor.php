<?php

use App\Enums\UserScope;
use App\Http\Controllers\Api\V1\ResetPassword\PasswordResetController;
use App\Http\Controllers\Api\V1\Vendor\BusinessTypeController;
use App\Http\Controllers\Api\V1\Vendor\LoginController;
use App\Http\Controllers\Api\V1\Vendor\LogoutController;
use App\Http\Controllers\Api\V1\Vendor\SetPasswordController;
use App\Http\Controllers\Api\V1\Vendor\VendorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function (): void {
    Route::get('/business-types', BusinessTypeController::class)
        ->name('vendor.business-types.index');

    Route::prefix('auth')->group(function (): void {
        Route::post('/registrations', VendorRegistrationController::class)
            ->name('vendor.registrations.store');

        Route::post('/login', LoginController::class)
            ->name('login');

        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
            ->defaults('scope', UserScope::VENDOR->value)
            ->name('vendor.auth.forgot-password');

        Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])
            ->defaults('scope', UserScope::VENDOR->value)
            ->name('vendor.auth.verify-otp');

        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
            ->defaults('scope', UserScope::VENDOR->value)
            ->name('vendor.auth.reset-password');

        Route::post('/set-password', SetPasswordController::class)
            ->name('set-password');

        Route::post('/logout', LogoutController::class)
            ->middleware('auth:web')
            ->name('logout');
    });
});
