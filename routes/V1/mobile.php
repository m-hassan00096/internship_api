
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Mobile\LoginMobileController;
use App\Http\Controllers\Api\V1\Mobile\LogoutMobileController;

Route::prefix('mobile')->group(function () {
    Route::post('/login', LoginMobileController::class);

    Route::post('/logout', LogoutMobileController::class)
        ->middleware('auth:sanctum');
});
