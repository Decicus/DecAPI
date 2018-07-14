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
        $blacklist = IpBlacklist
                ::where('ip_address', $ip)
                ->first();

        if (empty($blacklist)) {
            return $next($request);
        }

        Log::Error(sprintf('Blocked %s from accessing %s due to reason: %s', $ip, $request->fullUrl(), $blacklist->reason));
        abort(503);
    }
}
