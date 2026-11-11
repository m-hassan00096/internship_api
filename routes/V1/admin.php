<?php

use App\Http\Controllers\Api\V1\Admin\LoginController;
use App\Http\Controllers\Api\V1\Admin\LogoutController;
use App\Http\Controllers\Api\V1\Admin\VendorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', LoginController::class)
            ->name('login');

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
