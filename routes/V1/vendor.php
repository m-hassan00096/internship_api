<?php

use App\Enums\UserScope;
use App\Http\Controllers\Api\V1\ResetPassword\PasswordResetController;
use App\Http\Controllers\Api\V1\Vendor\BusinessTypeController;
use App\Http\Controllers\Api\V1\Vendor\CategoryController;
use App\Http\Controllers\Api\V1\Vendor\LoginController;
use App\Http\Controllers\Api\V1\Vendor\LogoutController;
use App\Http\Controllers\Api\V1\Vendor\PromotionController;
use App\Http\Controllers\Api\V1\Vendor\PromotionTypeController;
use App\Http\Controllers\Api\V1\Vendor\RoomController;
use App\Http\Controllers\Api\V1\Vendor\SetPasswordController;
use App\Http\Controllers\Api\V1\Vendor\ShowroomController;
use App\Http\Controllers\Api\V1\Vendor\VendorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function (): void {
    Route::get('/business-types', BusinessTypeController::class)
        ->name('vendor.business-types.index');

    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('vendor.categories.index');

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

    Route::middleware(['auth:web', 'scope:vendor'])
        ->prefix('showroom')
        ->name('vendor.showroom.')
        ->group(function (): void {
            Route::get('/', [ShowroomController::class, 'show'])->name('show');
            Route::get('/contract', [ShowroomController::class, 'contract'])->name('contract');
            Route::get('/verification', [ShowroomController::class, 'verification'])->name('verification');
            Route::post('/verification', [ShowroomController::class, 'applyVerification'])->name('verification.apply');
            Route::get('/branches', [ShowroomController::class, 'branches'])->name('branches');
        });

    Route::middleware(['auth:web', 'scope:vendor'])
        ->prefix('rooms')
        ->name('vendor.rooms.')
        ->group(function (): void {
            Route::get('/', [RoomController::class, 'index'])->name('index');
            Route::post('store', [RoomController::class, 'store'])->name('store');
            Route::get('show/{room}', [RoomController::class, 'show'])->name('show');
            Route::put('update/{room}', [RoomController::class, 'update'])->name('update');
            Route::delete('delete/{room}', [RoomController::class, 'destroy'])->name('destroy');
            Route::patch('status/{room}', [RoomController::class, 'changeStatus'])->name('change-status');

            // Images
            Route::prefix('{room}/images')->name('images.')->group(function (): void {
                Route::post('/', [RoomController::class, 'uploadImages'])->name('upload');
                Route::delete('/{image}', [RoomController::class, 'deleteImage'])->name('delete');
                Route::patch('/{image}/primary', [RoomController::class, 'setPrimaryImage'])->name('primary');
            });

            // Components & Attributes
            Route::prefix('{room}/components')->name('components.')->group(function (): void {
                Route::post('/', [RoomController::class, 'storeComponent'])->name('store');
                Route::put('/{component}', [RoomController::class, 'updateComponent'])->name('update');
                Route::delete('/{component}', [RoomController::class, 'destroyComponent'])->name('destroy');
            });
        });

    Route::middleware(['auth:web', 'scope:vendor'])
        ->group(function (): void {
            Route::get('/promotion-types', PromotionTypeController::class)
                ->name('vendor.promotion-types.index');

            Route::prefix('promotions')
                ->name('vendor.promotions.')
                ->group(function (): void {
                    Route::get('/', [PromotionController::class, 'index'])->name('index');
                    Route::post('/store', [PromotionController::class, 'store'])->name('store');
                    Route::get('/{promotion}/show', [PromotionController::class, 'show'])->name('show');
                    Route::delete('/{promotion}/delete', [PromotionController::class, 'destroy'])->name('destroy');
                });
        });
});
