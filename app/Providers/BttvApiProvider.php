<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as HttpClient;
use App\Services\BttvApiClient;
use App\Repositories\BttvApiRepository;

class BttvApiProvider extends ServiceProvider
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
        $this->app->singleton(BttvApiClient::class);
        $this->app->singleton(BttvApiRepository::class);
    }
}
