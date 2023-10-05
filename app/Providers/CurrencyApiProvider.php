<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\CurrencyApiRepository;
use App\Services\CurrencyApiClient;

class CurrencyApiProvider extends ServiceProvider
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
        $this->app->singleton(CurrencyApiClient::class);
        $this->app->singleton(CurrencyApiRepository::class);
    }
}
