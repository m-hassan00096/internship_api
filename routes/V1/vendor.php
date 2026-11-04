<?php

use App\Http\Controllers\Api\V1\Vendor\LoginController;
use App\Http\Controllers\Api\V1\Vendor\LogoutController;
use App\Http\Controllers\Api\V1\Vendor\SetupPasswordController;
use App\Http\Controllers\Api\V1\Vendor\VendorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function () {
    Route::post('/registrations', VendorRegistrationController::class)
        ->name('vendor.registrations.store');

    Route::post('/vendor-registrations', VendorRegistrationController::class)
        ->name('vendor.vendor-registrations.store');

    Route::post('/applications', VendorRegistrationController::class)
        ->name('vendor.applications.store');

    Route::post('/login', LoginController::class)
        ->name('login');

    Route::post('/setup-password', SetupPasswordController::class)
        ->name('setup-password');

    Route::post('/logout', LogoutController::class)
        ->middleware('auth:web')
        ->name('logout');
});
