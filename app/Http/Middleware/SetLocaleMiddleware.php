<?php

namespace App\Http\Middleware;

use App;
use Closure;

class SetLocaleMiddleware
{
    /**
     * Valid locales
     *
     * @var array
     */
    protected array $locales = [];

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
        $localeDirs = scandir(resource_path('lang'));

        $this->locales = array_filter($localeDirs, function($dir) {
            return !in_array($dir, ['.', '..']);
        });
    }

    /**
     * Validate locale from request parameter
     *
     * @param string $locale
     *
     * @return string The locale itself or the default locale (en).
     */
    private function getValidLocale(string $locale) : string
    {
        $locale = strtolower(trim($locale));
        if (in_array($locale, $this->locales)) {
            return $locale;
        }

        $locale = substr($locale, 0, 2);
        if (in_array($locale, $this->locales)) {
            return $locale;
        }

        return 'en';
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = $this->getValidLocale($request->input('lang', 'en'));
        App::setLocale($locale);

        return $next($request);
    }
}
