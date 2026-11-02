
<?php

use App\Http\Controllers\Api\V1\Vendor\LoginController;
use App\Http\Controllers\Api\V1\Vendor\LogoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function () {
    Route::post('/login', LoginController::class)
        ->name('login');

    Route::post('/logout', LogoutController::class)
        ->middleware('auth:web')
        ->name('logout');
});
