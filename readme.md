# DecAPI
[DecAPI](https://decapi.me/) is a personal project I started writing in 2014, which eventually became a big pile of undocumented, and badly written mess.

This is essentially an attempt at rewriting everything that DecAPI supports into something more structured and documented, while still keeping backwards compatibility to those applications that still rely on it, using the [Laravel framework](https://laravel.com/)

As of right now, the rewrite is in still in the early stages and very few things are supported. The list below will be kept up-to-date as to what is currently working.

If you're interested, a beta version is available under [beta.decapi.me](https://beta.decapi.me).

## Layout
The layout of the application can be reflected upon by looking at the [routes.php](app/Http/routes.php) file. Each group uses their own controller located in [app/Http/Controllers](app/Http/Controllers), and each sub-route usually has their own method in said controller.

The standard layout will be https://example.com/main-route/sub-route/parameter - where `parameter` can be something like the channel name.

To keep it backwards compatible, routes also support /main-route/sub-route?channel=decicus or /main-route/sub-route.php?channel=decicus.

## Current re-implemented features
This list of features will contain links to the current version hosted under [decapi.me](https://decapi.me/)

It _should_ function similarly or identical in this rewrite, even if the code hosted under [decapi.me](https://decapi.me/) is still the old code.

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
    - [Latest "status report" from their Steam 'news' section](https://decapi.me/dayz/status-report)
    - [Maps location names/searches to their respective coordinates/location on izurvive.com](https://decapi.me/dayz/izurvive)
        - Special thanks to [WastedUser](https://www.twitch.tv/wasteduser) for the `location name -> coordinates` data.

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

## License
[MIT License](LICENSE)
