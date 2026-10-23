
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\LoginController;
use App\Http\Controllers\Api\V1\Admin\LogoutController;

Route::prefix('admin')->group(function () {
    Route::post('/login', LoginController::class)
        ->name('login');

    Route::post('/logout', LogoutController::class)
        ->middleware('auth:web')
        ->name('logout');
});
