<?php

namespace App\Providers;

use App\Models\Package;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'room' => Room::class,
            'showroom' => Showroom::class,
            'package' => Package::class,
        ]);
    }
}
