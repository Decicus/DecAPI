<?php

namespace App\Http\Middleware;

use Closure;

use App\IpBlacklist;
use Cache;
use Log;

use Exception;

class IPBasedBlacklist
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
        try {
            $ip = $request->ip();
            $blacklist = Cache::rememberForever('ip_blacklist', function() {
                return IpBlacklist::all();
            });

            $blacklistIp = $blacklist
                           ->where('ip_address', $ip)
                           ->first();

            if (empty($blacklistIp)) {
                return $next($request);
            }

            Log::Info(sprintf('Blocked %s from accessing %s due to reason: %s', $ip, $request->fullUrl(), $blacklistIp->reason));
            return response()
                   ->view('errors.503', [], 503);
        }
        catch (Exception $ex)
        {
            // If the database connection errors for some reason, just let the request continue.
            // Most requests are _not_ blacklisted and it feels unfair to "punish" them because
            // of an error that shouldn't affect them at all.
            Log::error($ex);
            return next($request);
        }
    }
}
