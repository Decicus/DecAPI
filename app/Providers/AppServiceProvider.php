<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as HttpClient;
use App\Services\TwitchApiClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('GuzzleHttp\Client', function() {
            return new HttpClient([
                'headers' => [
                    'User-Agent' => env('DECAPI_USER_AGENT', 'DecAPI/1.0.0 (https://github.com/Decicus/DecAPI)'),
                ],
            ]);
        });

        $this->app->singleton('App\Services\TwitchApiClient', function() {
            return new TwitchApiClient(app('GuzzleHttp\Client'));
        });
    }
}
