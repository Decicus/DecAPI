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

Route::get('/', function () {
    return view('home');
});

Route::group(['prefix' => 'askfm'], function() {
    Route::get('rss', 'AskfmController@rss');
    Route::get('rss/{user}', 'AskfmController@rss');
});

Route::group(['prefix' => 'twitch'], function() {
    Route::get('highlight', 'TwitchController@highlight');
    Route::get('highlight/{channel}', 'TwitchController@highlight');

    Route::get('hosts', 'TwitchController@hosts');
    Route::get('hosts/{channel}', 'TwitchController@hosts');

    Route::get('team_members', 'TwitchController@teamMembers');
    Route::get('team_members/{team}', 'TwitchController@teamMembers');

    Route::get('uptime', 'TwitchController@uptime');
    Route::get('uptime/{channel}', 'TwitchController@uptime');
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
    //
});
