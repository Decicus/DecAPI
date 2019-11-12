<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TwitchEmotesApiClient;
use App\Repositories\TwitchEmotesApiRepository;

class TwitchEmotesApiProvider extends ServiceProvider
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
        $this->app->singleton(TwitchEmotesApiClient::class);
        $this->app->singleton(TwitchEmotesApiRepository::class);
    }
}
