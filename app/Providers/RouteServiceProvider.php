<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Removes one or more '@' characters from the beginning
     * of the string, if they are there.
     *
     * Primarily targeted towards /twitch routes where
     * `@` is used to mention Twitch usernames and thus causing a 404.
     *
     * In this scenario it should just remove the first `@` characters and continue
     * passing the value to the controller.
     *
     * @param string $value
     *
     * @return string
     */
    private function removeAtSigns($value)
    {
        return ltrim($value, '@');
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Remove one or more '@' if they're at the
         * beginning of the parameter.
         */
        Route::bind('channel', function($channel) {
            return $this->removeAtSigns($channel);
        });

        Route::bind('user', function($user) {
            return $this->removeAtSigns($user);
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    /**
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
