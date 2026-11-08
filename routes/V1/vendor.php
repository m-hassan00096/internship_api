<?php

use App\Http\Controllers\Api\V1\Vendor\LoginController;
use App\Http\Controllers\Api\V1\Vendor\LogoutController;
use App\Http\Controllers\Api\V1\Vendor\SetPasswordController;
use App\Http\Controllers\Api\V1\Vendor\VendorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function () {
    Route::prefix('auth')->group(function () {
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
