# DecAPI

[DecAPI](https://decapi.me/) is a personal project I started writing in 2014, which eventually became a big pile of undocumented, and badly written mess.

This is essentially an attempt at rewriting everything that DecAPI supports into something more structured and documented, while still keeping backwards compatibility to those applications that still rely on it, using the [Laravel framework](https://laravel.com/).

Pull requests are generally welcome for new features, but features that are implemented to support backwards compatibility I would prefer to implement myself.  
This is only because certain features have never been documented (not even in my [blog post covering my custom APIs](https://blog.decicus.com/custom-apis/)).

This is currently live under [decapi.me](https://decapi.me/).

## Layout

The layout of the application can be reflected upon by looking at the [routes/web.php](routes/web.php) file. Each group uses their own controller located in [app/Http/Controllers](app/Http/Controllers), and each sub-route usually has their own method in said controller.

The standard layout will be https://example.com/main-route/sub-route/parameter - where `parameter` can be something like the channel name.

To keep it backwards compatible, routes also support /main-route/sub-route?channel=decicus or /main-route/sub-route.php?channel=decicus.

## Re-implementation of features

All features will be rewritten to be mostly identical to current features.

What this means is that certain text output from endpoints might be changed, new parameters to modify functionality will be added (for some), but for the most part the functionality will remain identical.

Read [the documentation](https://docs.decapi.me/) to see how each endpoint functions.

Anything that for some reason did not get included in this rewrite, will still be hosted under [v1.decapi.me](https://v1.decapi.me/).  

## Requirements

The following things are required for setting this up:

- [Laravel 6.x's requirements](https://laravel.com/docs/6.x/installation#server-requirements)
- [A database system that Laravel supports](https://laravel.com/docs/6.x/database#introduction)
- [Composer](https://getcomposer.org/)

## Setup

**I only recommend setting this up for development purposes.**

- Rename `.env.example` to `.env` and fill in the information in the `.env` file. Primarly the database and Twitch information.
    - If you are setting this up on a publicly accessible environment, make sure to set the `APP_DEBUG` value to `false` to not leak any credentials.
    - **Twitch**: You can create a Twitch application here: [Twitch developer console](https://dev.twitch.tv/console) - The redirect URL has to be `http://your.url/auth/twitch/callback` and `TWITCH_REDIRECT_URI` in the `.env` file has to be set to the same URL.
    - **YouTube**: Read the [Getting Started](https://developers.google.com/youtube/v3/getting-started#before-you-start) page and [Creating API keys](https://developers.google.com/youtube/registering_an_application#Create_API_Keys) section.
    - **Papertrail**: This is (optionally) used for logging. If you wish to use it, register on [Papertrail](https://papertrailapp.com/) and set the `PAPERTRAIL_LOG_DESTINATION` to whatever Papertrail gives you that's in the `logsX.papertrailapp.com:YYYY` format.
        - `X` and `YYYY` are numbers, and are just **placeholders**.
    - **Steam**: You can obtain a Steam API key here: [Steam API Developer Portal](https://steamcommunity.com/dev)
    - **Twitter**: Create a [developer application on Twitter](https://apps.twitter.com/) and insert the consumer key & consumer secret.
    - **Fixer** - Currency API: To have access to all the currencies, you need to have a pain plan from [fixer.io](https://fixer.io/).
        - For a limited time you can also register for the legacy plan, which is a better version of the free plan: [Fixer - Important announcement](https://github.com/fixerAPI/fixer#fixer----important-announcement) (bottom of the section)
- Run `composer install` in the project directory.
- Run `php artisan key:generate` from the command line in the base project directory, to generate the application key.
- Run `php artisan migrate` from the command line in the base project directory.
- Point your web server to the `/public` directory of the repo.
    - I recommend using apache2 and configuring it to set `AllowOverride` to `All` for the specific directory in the vhost, so the `.htaccess` file can set the settings.
- Setup the task scheduler by pointing a cron entry to `* * * * * php /path/to/decapi/artisan schedule:run >> /dev/null 2>&1`.
    - You can see what commands the scheduler runs in `app/Console/Kernel.php`.

## Documentation

Documentation is currently work in progress and can be found here:

- [Website (docs.decapi.me)](https://docs.decapi.me/)
- [Repository of website (Decicus/DecAPI-Docs)](https://github.com/Decicus/DecAPI-Docs)

## Bugs & reports

If you find a bug or an issue, please create an issue in this repository.

If it's a security issue and you'd like to contact me privately, please send me an email at <alex@thomassen.xyz>.

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

Rate limits per route are separate from each other.  
If you've sent 45 requests to `/steam` routes, you will still have the ability to send another 100 requests to `/twitch` routes.

- `/twitch/*` - All sub-routes under `/twitch`
    - Limit: 100 requests per 60 seconds.
- `/steam/*` - All sub-routes under `/steam`
    - Limit: 15 requests per 60 seconds.

## License

[MIT License](LICENSE)

## Special thanks to

- [xgerhard](https://github.com/xgerhard) - For implementing the /twitch/subage & /twitch/latest_sub routes.
- [TwitchEmotes.com](https://twitchemotes.com/) for providing information around channel emotes & badges.
