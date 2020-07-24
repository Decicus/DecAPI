<?php

namespace App\Http\Middleware;

use Closure;

class TwitchRemoveAtSigns
{
    /**
     * Removes one or more '@' characters from the beginning
     * of the string, if they are there.
     *
     * In this scenario it should just remove the first `@` characters
     * before merging the new strings back into the existing request.
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
     * Remove leading `@` characters for specific
     * parameters (mainly `channel` and `user`).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $old = $request->all();

        /**
         * Neither `channel` or `user` is specified
         * So we don't need to modify anything.
         */
        if (!isset($old['channel']) && !isset($old['user'])) {
            return $next($request);
        }

        /**
         * Store new values that should be merged into the request.
         */
        $new = [];

        /**
         * Replace for `channel` parameter, if it's set.
         */
        if (isset($old['channel'])) {
            $new['channel'] = $this->removeAtSigns($old['channel']);
        }

        /**
         * Replace for `user` parameter, if it's set.
         */
        if (isset($old['user'])) {
            $new['user'] = $this->removeAtSigns($old['user']);
        }

        /**
         * Merge/replace the new strings into the request.
         */
        $request->replace($new);

        return $next($request);
    }
}
