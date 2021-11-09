<?php

namespace App\Http\Middleware;

use Closure;

use App\Helpers\Helper;
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

            $reason = $blacklistIp->reason ?? '';
            $contact = env('CONTACT_URL', null);
            $format = 'Your IP address has been blocked for the following reason: %s';

            if (!empty($contact)) {
                $format .= sprintf(' | Please contact for more information: %s', $contact);
            }

            return Helper::text(sprintf($format, trim($reason)), 429);
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
