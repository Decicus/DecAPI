# DecAPI

## What is DecAPI?

DecAPI started out as an API designed primarily for Twitch chatbots that supported sending requests to external APIs.  
These chatbots would send requests to the APIs and then just return the response directly, which is the whole reason DecAPI _mainly_ responds in plaintext.

While the primary usage of DecAPI is still related to Twitch chatbots, it's also used by other developers for stream overlays, websites etc.  
Even though I generally allow everyone to rely on DecAPI for their data, I still recommend relying on the direct APIs whenever possible.

## Development history

[DecAPI](https://decapi.me/) is a personal project I started writing in 2014 (V1), which eventually became a big pile of undocumented, and badly written mess.

The current version of DecAPI (V2) was started in early 2016 and is an attempt to rewrite the codebase, while also keeping backwards compatibility, but also have it somewhat structured (unlike V1).  
V2 is based on the [Laravel framework](https://laravel.com/).

## Contributing

The `CONTRIBUTING` document has been moved to [`.github/CONTRIBUTING.md`](/.github/CONTRIBUTING.md)

## Layout

The layout of the application can be reflected upon by looking at the [routes/web.php](routes/web.php) file. Each group uses their own controller located in [app/Http/Controllers](app/Http/Controllers), and each sub-route usually has their own method in said controller.

The standard layout will be `https://example.com/main-route/sub-route/parameter` - where `parameter` can be something like the channel name.

To keep it backwards compatible, routes also support `/main-route/sub-route?channel=decicus` or `/main-route/sub-route.php?channel=decicus`.

## Requirements

The following things are required for setting this up:

- [Laravel 8.x's requirements](https://laravel.com/docs/8.x/installation#server-requirements)
- [A database system that Laravel supports](https://laravel.com/docs/8.x/database#introduction)
    - The live version of DecAPI uses MariaDB/MySQL, but for development reasons PostgreSQL/SQLite should work fine too.
- [Composer](https://getcomposer.org/)

## Setup

**I only recommend setting this up for development purposes.**

- Rename `.env.example` to `.env` and fill in the information in the `.env` file. Primarly the database and Twitch information.
    - If you are setting this up on a publicly accessible environment, make sure to set the `APP_DEBUG` value to `false` to not leak any credentials.
    - **Twitch**: You can create a Twitch application here: [Twitch developer console](https://dev.twitch.tv/console) - The redirect URL has to be `http://your.url/auth/twitch/callback` and `TWITCH_REDIRECT_URI` in the `.env` file has to be set to the same URL.
    - **YouTube**: Read the [Getting Started](https://developers.google.com/youtube/v3/getting-started#before-you-start) page and [Creating API keys](https://developers.google.com/youtube/registering_an_application#Create_API_Keys) section.
    - **Papertrail**: This is (optionally) used for external error logging. If you wish to use it, register on [Papertrail](https://papertrailapp.com/) and set the `PAPERTRAIL_LOG_DESTINATION` to whatever Papertrail gives you that's in the `logsX.papertrailapp.com:YYYY` format.
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

- [Documentation website (docs.decapi.me)](https://docs.decapi.me/)
- [Repository of documentation website (Decicus/DecAPI-Docs)](https://github.com/Decicus/DecAPI-Docs)

## Bugs & reports

If you find a bug or an issue, please create an issue in this repository.

If it's a security issue and you'd like to contact me privately, please send me an email at <alex@thomassen.xyz>.

## Rate limits

Certain routes may have rate limiting applied to them to prevent abuse or to make sure DecAPI doesn't get blocked by the API provider.  
The rate limits are set to something I consider "fair". Which primarily means they are set to something that should not hinder the normal user, but also should not allow them to go spam requests for no good reason.

Rate limiting is done by using Laravel's `throttle` middleware. This means you can check HTTP headers sent with the request to figure out information about your rate-limit:

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
