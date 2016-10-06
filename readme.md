# DecAPI
[DecAPI](https://decapi.me/) is a personal project I started writing in 2014, which eventually became a big pile of undocumented, and badly written mess.

This is essentially an attempt at rewriting everything that DecAPI supports into something more structured and documented, while still keeping backwards compatibility to those applications that still rely on it, using the [Laravel framework](https://laravel.com/).

As of right now, there is no ETA when this will be "complete". The idea is to keep adding features until I feel like it's time for another rewrite.  
Pull requests are generally welcome for new features, but generally features that are implemented to support backwards compatibility I would prefer to implement myself.  
This is only because certain features have never been documented (not even in my [blog post covering my custom APIs](https://blog.thomassen.xyz/custom-apis/)).

If you're interested, a beta version is available under [beta.decapi.me](https://beta.decapi.me).

## Layout
The layout of the application can be reflected upon by looking at the [routes.php](app/Http/routes.php) file. Each group uses their own controller located in [app/Http/Controllers](app/Http/Controllers), and each sub-route usually has their own method in said controller.

The standard layout will be https://example.com/main-route/sub-route/parameter - where `parameter` can be something like the channel name.

To keep it backwards compatible, routes also support /main-route/sub-route?channel=decicus or /main-route/sub-route.php?channel=decicus.

## Current re-implemented features
This list of features will contain links to the current version hosted under [decapi.me](https://decapi.me/)

It _should_ function similarly or identical in this rewrite, even if the code hosted under [decapi.me](https://decapi.me/) is still the old code.

Anything that for some reason did not get included in this rewrite, will still be hosted under [old.decapi.me](https://old.decapi.me/).  
There is also a fallback route setup to redirect all requests to the [old.decapi.me](https://old.decapi.me/) URL, if it cannot find a valid route in the rewrite.

- Twitch
    - [Follow date and time](https://decapi.me/twitch/followed?user=decicus&channel=twitch)
    - [Latest highlight](https://decapi.me/twitch/highlight?channel=decicus)
    - [List of hosts](https://decapi.me/twitch/hosts?channel=decicus)
    - [List of ingests](https://decapi.me/twitch/ingests)
    - [List of team members](https://decapi.me/twitch/team_members?team=theblacklist)
    - [Current subcount (requires authentication)](https://decapi.me/twitch/subcount?channel=decicus)
    - [Current uptime](https://decapi.me/twitch/uptime?channel=decicus)
- ASKfm
    - [RSS/Atom feed of user questions/answers](https://decapi.me/askfm/rss?user=xangold)
- BetterTTV
    - [Look up channel emotes](https://decapi.me/bttv/?channel=decicus)
    - [Custom API for displaying emotes in chat](https://decapi.me/bttv/emotes?channel=decicus)
- DayZ
    - [Latest "status report" from their blog](https://decapi.me/dayz/status-report)
    - [Latest "status report" from their Steam 'news' section](https://decapi.me/dayz/steam-status-report)
    - [Maps location names/searches to their respective coordinates/location on izurvive.com](https://decapi.me/dayz/izurvive)
        - Special thanks to [WastedUser](https://www.twitch.tv/wasteduser) for the `location name -> coordinates` data.
    - [Queries DayZ servers for their current player count](https://decapi.me/dayz/players)
- Lever
    - [RSS feed of the available Twitch jobs](https://decapi.me/lever/twitch)
- Misc
    - [Currency converter](https://decapi.me/misc/currency)
- Twitter
    - [Latest tweet](https://decapi.me/twitter/latest?name=decicus)
    - [Latest tweet URL](https://decapi.me/twitter/latest_url?name=decicus)
    - [Latest tweet ID](https://decapi.me/twitter/latest_id?name=decicus)
- YouTube
    - [Latest public video for a specified channel](https://decapi.me/youtube/latest_video?user=decicus)
    - [Video ID by search](https://decapi.me/youtube/videoid?search=barbie%20girl)
- Steam
    - [Amount of hours in a specified game](https://decapi.me/steam/hours?id=76561197997719567&appid=221100)
    - [Current gameserver (supported games are limited)](https://decapi.me/steam/server_ip?id=76561197997719567)

## Requirements
The following things are required for setting this up:
- [Laravel's requirements](https://laravel.com/docs/5.2/installation#server-requirements)
- [A database system that Laravel supports](https://laravel.com/docs/5.2/database#introduction)
- [Composer](https://getcomposer.org/)

## Setup
**I only recommend setting this up for development reasons;**
- Rename `.env.example` to `.env` and fill in the information. Primarly the database and Twitch information.
    - You can create a Twitch application here: https://www.twitch.tv/settings/connections. The redirect URL has to be `http://your.url/auth/twitch` and needs to be set the same under `TWITCH_REDIRECT_URI` in the `.env` file.
- Run `composer install` in the project directory.
- Run `php artisan migrate` from the command line in the base project directory.
- Point your web server to the `/public` directory of the repo.
    - I recommend using apache2 and configuring it to set `AllowOverride` to `All` for the specific directory in the vhost, so the `.htaccess` file can set the settings.

## Documentation
Documentation covering all the endpoints will be available at some point in the future, probably closer to the "full release" of this project.

## Rate limits
Certain routes may have rate limiting applied to them to prevent abuse.  
I do not plan on applying rate limits on many routes, only those I notice are used a lot by one user.  
The rate limits will also be set to something I consider "fair". Which primarily means they will be set to something that should not hinder the normal user, but also should not allow them to go spam requests for no good reason.

Rate limiting is done by using Laravel's `throttle` middleware. This means you can check headers sent with the request to figure out information about your rate-limit:

- `X-RateLimit-Limit` - How many requests per 1 minute (60 seconds) is allowed.
- `X-RateLimit-Remaining` - How many requests you have left for this time period.
- `Retry-After` - How many seconds until you can make requests again (Only when you have actually hit your rate limit).
    - Another note: If you have hit your rate limit, you will receive a `429 Too many requests` HTTP status code.

Below is an overview over what routes are currently rate limited. If the route is not specified, it does not have a rate limit.

- `/twitch/uptime`
    - Limit: 100 requests per 60 seconds.

## License
[MIT License](LICENSE)
