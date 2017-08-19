# DecAPI
[DecAPI](https://decapi.me/) is a personal project I started writing in 2014, which eventually became a big pile of undocumented, and badly written mess.

This is essentially an attempt at rewriting everything that DecAPI supports into something more structured and documented, while still keeping backwards compatibility to those applications that still rely on it, using the [Laravel framework](https://laravel.com/).

Pull requests are generally welcome for new features, but features that are implemented to support backwards compatibility I would prefer to implement myself.  
This is only because certain features have never been documented (not even in my [blog post covering my custom APIs](https://blog.decicus.com/custom-apis/)).

This is currently live under [decapi.me](https://decapi.me/).

## Layout
The layout of the application can be reflected upon by looking at the [routes.php](app/Http/routes.php) file. Each group uses their own controller located in [app/Http/Controllers](app/Http/Controllers), and each sub-route usually has their own method in said controller.

The standard layout will be https://example.com/main-route/sub-route/parameter - where `parameter` can be something like the channel name.

To keep it backwards compatible, routes also support /main-route/sub-route?channel=decicus or /main-route/sub-route.php?channel=decicus.

## Re-implementation of features
All features will be rewritten to be mostly identical to current features.

What this means is that certain text output from endpoints might be changed, new parameters to modify functionality will be added (for some), but for the most part the functionality will remain identical.

Read [the documentation](https://docs.decapi.me/) to see how each endpoint functions.

Anything that for some reason did not get included in this rewrite, will still be hosted under [old.decapi.me](https://old.decapi.me/).  
There is also a fallback route setup to redirect all requests to the [old.decapi.me](https://old.decapi.me/) URL, if it cannot find a valid route in the rewrite.

## Requirements
The following things are required for setting this up:
- [Laravel 5.2's requirements](https://laravel.com/docs/5.2/installation#server-requirements)
- [A database system that Laravel supports](https://laravel.com/docs/5.2/database#introduction)
- [Composer](https://getcomposer.org/)

## Setup
**I only recommend setting this up for development purposes.**
- Rename `.env.example` to `.env` and fill in the information. Primarly the database and Twitch information.
    - You can create a Twitch application here: https://www.twitch.tv/settings/connections. The redirect URL has to be `http://your.url/auth/twitch/callback` and `TWITCH_REDIRECT_URI` in the `.env` file has to be set to the same URL.
- Run `composer install` in the project directory.
- Run `php artisan migrate` from the command line in the base project directory.
- Point your web server to the `/public` directory of the repo.
    - I recommend using apache2 and configuring it to set `AllowOverride` to `All` for the specific directory in the vhost, so the `.htaccess` file can set the settings.
- Setup the task scheduler by pointing a cron entry to `* * * * * php /path/to/decapi/artisan schedule:run >> /dev/null 2>&1`.
    - You can see what commands the scheduler runs in `app/Console/Kernel.php`.

## Documentation
Documentation is currently work in progress and can be found here:

- [Website (docs.decapi.me)](https://docs.decapi.me/)
- [Repository of website (Decicus/DecAPI-Docs)](https://github.com/Decicus/DecAPI-Docs)

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
