
<?php

use App\Enums\UserScope;
use App\Http\Controllers\Api\V1\Client\LoginController;
use App\Http\Controllers\Api\V1\Client\LogoutController;
use App\Http\Controllers\Api\V1\ResetPassword\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', LoginController::class)->name('login');

        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
            ->defaults('scope', UserScope::CLIENT->value)
            ->name('client.auth.forgot-password');

        Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])
            ->defaults('scope', UserScope::CLIENT->value)
            ->name('client.auth.verify-otp');

        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
            ->defaults('scope', UserScope::CLIENT->value)
            ->name('client.auth.reset-password');

        Route::post('/logout', LogoutController::class)
            ->middleware('auth:web')
            ->name('logout');
    });
});
