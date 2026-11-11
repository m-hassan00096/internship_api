<?php

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

        Route::post('/set-password', SetPasswordController::class)
            ->name('set-password');

        Route::post('/logout', LogoutController::class)
            ->middleware('auth:web')
            ->name('logout');
    });
});
