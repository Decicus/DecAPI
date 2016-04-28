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

Route::get('/', ['as' => 'home', function () {
    return view('home');
}]);

Route::group(['prefix' => 'askfm'], function() {
    Route::get('rss', 'AskfmController@rss');
    Route::get('rss/{user}', 'AskfmController@rss');
});

Route::group(['prefix' => 'twitch'], function() {
    $channelRegex = '([A-z0-9]{1,25})';

    Route::get('/', 'TwitchController@base');

    Route::get('{followed}/{user?}/{channel?}', 'TwitchController@followed')
        ->where('followed', '(followed\.php|followed)')
        ->where('user', $channelRegex)
        ->where('channel', $channelRegex);

    Route::get('{highlight}/{channel?}', 'TwitchController@highlight')
        ->where('highlight', '(highlight\.php|highlight)')
        ->where('channel', $channelRegex);

    Route::get('{hosts}/{channel?}', 'TwitchController@hosts')
        ->where('hosts', '(hosts\.php|hosts)')
        ->where('channel', $channelRegex);

    Route::get('{ingests}', 'TwitchController@ingests')
        ->where('ingests', '(ingests\.php|ingests)');

    Route::get('subscriber_emotes/{channel?}', 'TwitchController@subEmotes')
        ->where('channel', $channelRegex);

    Route::get('{team_members}/{team?}', 'TwitchController@teamMembers')
        ->where('team_members', '(team_members\.php|team_members)')
        ->where('team', '([A-z0-9]{1,40})');

    Route::get('{uptime}/{channel?}', 'TwitchController@uptime')
        ->where('uptime', '(uptime\.php|uptime)')
        ->where('channel', $channelRegex);
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    Route::group(['prefix' => 'auth'], function() {
        Route::get('twitch', 'TwitchAuthController@auth');
    });

    Route::group(['prefix' => 'twitch'], function() {
        $channelRegex = '([A-z0-9]{1,25})';
        Route::get('{subcount}/{channel?}', 'TwitchController@subcount')
            ->where('subcount', '(subcount\.php|subcount)')
            ->where('channel', $channelRegex);
    });
});
