<?php

namespace App\Http\Middleware;

use Closure;

use App\IpBlacklist;
use Log;

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
        $ip = $request->ip();
        /*$blacklist = IpBlacklist
                ::where('ip_address', $ip)
                ->first();*/

        $blacklistedIps = explode(',', env('BLACKLISTED_IPS', ''));

        if (!in_array($ip, $blacklistedIps)) {
            return $next($request);
        }

        Log::Info(sprintf('Blocked %s from accessing %s due to reason: %s', $ip, $request->fullUrl(), $blacklist->reason));
        abort(503);
    }
}
