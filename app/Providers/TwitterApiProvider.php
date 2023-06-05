<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TwitterApiClient;
use App\Repositories\TwitterApiRepository;

class TwitterApiProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(TwitterApiClient::class);
        $this->app->singleton(TwitterApiRepository::class);
    }
}
