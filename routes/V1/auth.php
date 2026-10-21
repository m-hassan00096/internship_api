
<?php

use App\Http\Controllers\Api\V1\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class);


