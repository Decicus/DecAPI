<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

use App\RateLimitApiKeys as ApiKey;

class RateLimitWithWhitelist extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return mixed
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        $apiKey = trim($request->header('x-api-key', ''));
        if (empty($apiKey)) {
            return parent::handle($request, $next, $maxAttempts, $decayMinutes);
        }

        $apiKeyModel = ApiKey
                ::where('api_key', $apiKey)
                ->where('enabled', true)
                ->get();

        if ($apiKeyModel->isEmpty()) {
            return parent::handle($request, $next, $maxAttempts, $decayMinutes);
        }

        return $next($request);
    }
}
