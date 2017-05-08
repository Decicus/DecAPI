<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => 'web'], function() {
    Route::get('/{index?}', ['as' => 'home', 'uses' => 'GeneralController@home'])
        ->where('index', '(index(.php)?|home)');

    Route::group(['prefix' => 'askfm'], function() {
        Route::get('rss/{user?}', 'AskfmController@rss');
    });

    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function() {
        Route::group(['prefix' => 'twitch', 'as' => 'twitch.'], function() {
            Route::get('/', ['as' => 'base', 'uses' => 'TwitchAuthController@redirect']);
            Route::get('callback', ['as' => 'callback', 'uses' => 'TwitchAuthController@callback']);
            Route::get('logout', ['as' => 'logout', 'uses' => 'TwitchAuthController@logout']);
        });
    });

    Route::group(['prefix' => 'br', 'as' => 'br.'], function() {
        Route::group(['prefix' => 'player', 'as' => 'player.'], function() {
            Route::get('{id}/{type?}', ['as' => 'summary', 'uses' => 'BattleRoyaleController@player'])
                ->where('id', '([A-z0-9]+)')
                ->where('type', '(.*+)');
        });
    });

    Route::group(['prefix' => 'bttv', 'as' => 'bttv.'], function() {
        Route::get('/', ['as' => 'home', 'uses' => 'BttvController@home']);
        Route::get('{emotes}/{channel?}', ['as' => 'emotes', 'uses' => 'BttvController@emotes'])
            ->where('emotes', '(emotes(\.php)?)')
            ->where('channel', '([A-z0-9]{1,30})');
    });

    Route::group(['prefix' => 'dayz', 'as' => 'dayz.'], function() {
        Route::get('/', ['as' => 'base', 'uses' => 'DayZController@base']);
        Route::get('players', ['as' => 'players', 'uses' => 'DayZController@players']);
        Route::get('izurvive', ['as' => 'izurvive', 'uses' => 'DayZController@izurvive']);
        Route::get('random-server', ['as' => 'randomServer', 'uses' => 'DayZController@randomServer']);
        Route::get('status-report', ['as' => 'statusReport', 'uses' => 'DayZController@statusReport']);
        Route::get('steam-status-report', ['as' => 'steamStatusReport', 'uses' => 'DayZController@steamStatusReport']);
    });

    Route::group(['prefix' => 'lever', 'as' => 'lever.'], function() {
        Route::get('/', ['as' => 'base', 'uses' => 'LeverController@base']);
        Route::get('{twitch}', ['as' => 'twitch', 'uses' => 'LeverController@twitch'])
            ->where('twitch', '(twitch(\.php)?)');
    });

    Route::group(['prefix' => 'math', 'as' => 'math.'], function() {
        Route::get('/', ['as' => 'evaluate', 'uses' => 'MathController@evaluate']);
    });

    Route::group(['prefix' => 'misc', 'as' => 'misc.'], function() {
        Route::get('{currency}', ['as' => 'currency', 'uses' => 'MiscController@currency'])
            ->where('currency', '(currency(\.php)?)');
    });

    Route::group(['prefix' => 'r6', 'as' => 'r6.'], function() {
        Route::get('patch_notes', ['as' => 'patch_notes', 'uses' => 'Rainbow6Controller@patchNotes']);
    });

    Route::group(['prefix' => 'random', 'as' => 'random.'], function() {
        Route::get('{number}/{min?}/{max?}', ['as' => 'number', 'uses' => 'RandomController@number'])
            ->where('number', '(num(ber)?)')
            ->where('min', '((-)?[\d]+)')
            ->where('max', '((-)?[\d]+)');
    });

    Route::group(['prefix' => 'steam', 'as' => 'steam.'], function() {
        Route::get('/', ['as' => 'base', 'uses' => 'SteamController@base']);

        Route::get('connect/{appId?}/{parameters?}', ['as' => 'connect', 'uses' => 'SteamController@connect'])
            ->where('appId', '[\d]{1,8}')
            ->where('parameters', '.*');

        Route::get('currencies', ['as' => 'currencies', 'uses' => 'SteamController@listCurrencies']);

        Route::get('gamesearch', ['as' => 'gamesearch', 'uses' => 'SteamController@gameInfoBySearch']);

        Route::get('{hours}/{player_id?}/{app_id?}/{readable?}', ['as' => 'hours', 'uses' => 'SteamController@hours'])
            ->where('hours', '(hours(\.php)?)')
            ->where('player_id', '([0-9]+)')
            ->where('app_id', '([0-9]+)')
            ->where('readable', 'readable');

        Route::get('{server_ip}/{id?}', ['as' => 'server_ip', 'uses' => 'SteamController@serverIp'])
            ->where('server_ip', '(server_ip(.php)?)')
            ->where('id', '([0-9]+)');
    });

    Route::group(['prefix' => 'twitch', 'as' => 'twitch.'], function() {
        $channelRegex = '([A-z0-9]{1,50})';

        Route::get('/', 'TwitchController@base');

        Route::group(['prefix' => 'blog', 'as' => 'blog.'], function() {
            Route::get('latest', ['as' => 'latest', 'uses' => 'TwitchBlogController@latest']);
        });

        Route::get('chat_rules/{channel?}', ['as' => 'chat_rules', 'uses' => 'TwitchController@chatRules'])
            ->where('channel', $channelRegex);

        Route::get('clusters/{channel?}', 'TwitchController@clusters')
            ->where('channel', $channelRegex);

        Route::get('{creation}/{channel?}', 'TwitchController@creation')
            ->where('creation', '(creation(\.php)?)')
            ->where('channel', $channelRegex);

        Route::get('emoteslots/{channel?}', 'TwitchController@emoteslots')
            ->where('channel', $channelRegex);

        Route::get('followage/{channel?}/{user?}', 'TwitchController@followAge')
            ->where('channel', $channelRegex)
            ->where('user', $channelRegex);

        Route::get('followcount/{channel?}', 'TwitchController@followCount')
            ->where('channel', $channelRegex);

        Route::get('{followed}/{channel?}/{user?}', 'TwitchController@followed')
            ->where('followed', '(followed(\.php)?)')
            ->where('channel', $channelRegex)
            ->where('user', $channelRegex);

        Route::get('{followers}/{channel?}', 'TwitchController@followers')
            ->where('followers', '(followers(\.php)?)')
            ->where('channel', $channelRegex);

        Route::get('{gameOrStatus}/{channel?}', 'TwitchController@gameOrStatus')
            ->where('gameOrStatus', '(game|status|title)')
            ->where('channel', $channelRegex);

        Route::get('help/{search?}', ['as' => 'help', 'uses' => 'TwitchController@help'])
            ->where('search', '.*');

        Route::get('{highlight}/{channel?}', 'TwitchController@highlight')
            ->where('highlight', '(highlight(\.php)?)')
            ->where('channel', $channelRegex);

        Route::get('{highlight_random}/{channel?}', ['as' => 'highlight_random', 'uses' => 'TwitchController@highlightRandom'])
            ->where('highlight_random', '(highlight_random(\.php)?)')
            ->where('channel', $channelRegex);

        Route::get('{hosts}/{channel?}', 'TwitchController@hosts')
            ->where('hosts', '(hosts(\.php)?)')
            ->where('channel', $channelRegex);

        Route::get('hostscount/{channel?}', 'TwitchController@hostscount')
            ->where('channel', $channelRegex);

        Route::get('id/{user?}', ['as' => 'id', 'uses' => 'TwitchController@id'])
            ->where('user', $channelRegex);

        Route::get('{ingests}', 'TwitchController@ingests')
            ->where('ingests', '(ingests(\.php)?)');

        Route::get('multi/{streams?}', ['as' => 'multi', 'uses' => 'TwitchController@multi'])
            ->where('streams', '([A-z0-9_\s])+');

        Route::get('random_user/{channel?}', ['as' => 'random_viewer', 'uses' => 'TwitchController@randomUser'])
            ->where('channel', $channelRegex);

        Route::get('{subcount}/{channel?}', ['as' => 'subcount', 'uses' => 'TwitchController@subcount'])
            ->where('subcount', '(subcount(\.php)?)')
            ->where('channel', $channelRegex);

        Route::get('subscriber_emotes/{channel?}', 'TwitchController@subEmotes')
            ->where('channel', $channelRegex);

        Route::get('{team_members}/{team?}', 'TwitchController@teamMembers')
            ->where('team_members', '(team_members(\.php)?)')
            ->where('team', '([A-z0-9]{1,40})');

        Route::get('upload/{channel?}', 'TwitchController@upload')
            ->where('channel', $channelRegex);

        Route::group(['middleware' => 'throttle:100'], function() {
            Route::get('{uptime}/{channel?}', 'TwitchController@uptime')
                ->where('uptime', '(uptime(\.php)?)')
                ->where('channel', '([A-z0-9]){1,25}');
        });

        Route::get('viewercount/{channel?}', 'TwitchController@viewercount')
            ->where('channel', $channelRegex);
    });

    Route::group(['prefix' => 'twitter', 'as' => 'twitter.'], function() {
        Route::get('{latest}/{name?}', ['as' => 'latest', 'uses' => 'TwitterController@latest'])
            ->where('latest', '(latest(\.php)?|latest_url(\.php)?|latest_id(\.php)?)')
            ->where('name', '([A-z0-9]+)');

        Route::get('{tweet}/{name?}', ['as' => 'tweet', 'uses' => 'TwitterController@tweet'])
            ->where('tweet', '(tweet(\.php)?)')
            ->where('name', '([A-z0-9]+)');
    });

    Route::group(['prefix' => 'youtube', 'as' => 'youtube'], function() {
        Route::get('{latest_video}', ['as' => 'latest_video', 'uses' => 'YouTubeController@latestVideo'])
            ->where('latest_video', '(latest_video(\.php)?)');

        Route::get('latest_pl_video', ['as' => 'latest_pl_video', 'uses' => 'YouTubeController@latestPlVideo']);

        Route::get('{videoid}/{search?}', ['as' => 'videoid', 'uses' => 'YouTubeController@videoId'])
            ->where('videoid', '(videoid(\.php)?)')
            ->where('search', '(.*+)');
    });
});

Route::any('{fallback}', ['as' => 'fallback', 'uses' => 'GeneralController@fallback'])
    ->where('fallback', '.*');
