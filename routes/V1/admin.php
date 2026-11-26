<?php

use App\Enums\UserScope;
use App\Http\Controllers\Api\V1\Admin\LoginController;
use App\Http\Controllers\Api\V1\Admin\LogoutController;
use App\Http\Controllers\Api\V1\Admin\ShowroomContractController;
use App\Http\Controllers\Api\V1\Admin\ShowroomController;
use App\Http\Controllers\Api\V1\Admin\ShowroomPackageController;
use App\Http\Controllers\Api\V1\Admin\ShowroomRoomController;
use App\Http\Controllers\Api\V1\Admin\ShowroomVerificationController;
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

    Route::prefix('showrooms')
        ->middleware(['auth:web', 'scope:admin'])
        ->name('admin.showrooms.')
        ->group(function (): void {
            Route::get('/', [ShowroomController::class, 'index'])->name('index');
            Route::post('/', [ShowroomController::class, 'store'])->name('store');
            Route::get('/{id}', [ShowroomController::class, 'show'])->whereNumber('id')->name('show');
            Route::patch('/{id}/status', [ShowroomController::class, 'updateStatus'])->whereNumber('id')->name('update-status');

            // Rooms Module
            Route::prefix('rooms')->group(function (): void {
                Route::get('/{id}', [ShowroomRoomController::class, 'index'])
                    ->whereNumber('id')
                    ->name('rooms');
            });

            // Packages Module
            Route::prefix('packages')->group(function (): void {
                Route::get('/{id}', [ShowroomPackageController::class, 'index'])
                    ->whereNumber('id')
                    ->name('packages');
            });

            // Contracts Module
            Route::prefix('contracts')->group(function (): void {
                Route::get('/{id}', [ShowroomContractController::class, 'show'])
                    ->name('contracts');
                Route::get('/{id}/history', [ShowroomContractController::class, 'history'])
                    ->name('history');
                Route::post('/{id}/renew', [ShowroomContractController::class, 'renew'])
                    ->name('renew');
            });

            // Verification Module
            Route::prefix('verifications')->group(function (): void {
                Route::get('/{id}', [ShowroomVerificationController::class, 'history'])
                    ->name('verifications');
                Route::post('/{id}', [ShowroomVerificationController::class, 'manage'])
                    ->name('verification');
            });

        });
});
