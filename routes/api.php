<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require base_path('routes/V1/admin.php');
    require base_path('routes/V1/vendor.php');
    require base_path('routes/V1/client.php');
    require base_path('routes/V1/mobile.php');
});


