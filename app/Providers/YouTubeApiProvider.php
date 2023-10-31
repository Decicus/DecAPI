<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\YouTubeApiRepository;

class YouTubeApiProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {}

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(YouTubeApiRepository::class);
    }
}
