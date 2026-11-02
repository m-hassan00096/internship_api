
<?php

use App\Http\Controllers\Api\V1\Mobile\LoginMobileController;
use App\Http\Controllers\Api\V1\Mobile\LogoutMobileController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', LoginMobileController::class);

    Route::post('/logout', LogoutMobileController::class)
        ->middleware('auth:sanctum');
});
