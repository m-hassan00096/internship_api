
<?php

use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\LogoutController;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class);
Route::post('/logout', LogoutController::class);

