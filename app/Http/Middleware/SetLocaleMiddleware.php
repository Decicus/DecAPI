<?php

namespace App\Http\Middleware;

use App;
use Closure;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * Filter away invalid characters from specified locale
         */
        $locale = preg_replace('[^A-z-]', '', $request->input('lang', 'en'));
        App::setLocale($locale);

        return $next($request);
    }
}
