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
        $locale = $request->input('lang', 'en');
        App::setLocale($locale);

        return $next($request);
    }
}
