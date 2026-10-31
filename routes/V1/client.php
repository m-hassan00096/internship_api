
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Client\LoginController;
use App\Http\Controllers\Api\V1\Client\LogoutController;

Route::prefix('client')->group(function () {
    Route::post('/login', LoginController::class)
        ->name('login');

    Route::post('/logout', LogoutController::class)
        ->middleware('auth:web')
        ->name('logout');
});
