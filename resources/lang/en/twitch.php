<?php

/**
 * English translation strings for generic Twitch-related errors.
 */
return [
    /**
     * Related to chat
     */
    'no_chat_rules' => ':channel does not have any chat rules set.',
    'error_occurred_chat_clusters' => 'An error occurred retrieving chat clusters.',
    'error_retrieving_chat_users' => 'There was an error retrieving users for channel: ',
    'empty_chat_user_list' => 'The list of users is empty.',
    'channel_missing_subemotes' => 'This channel does not have any subscriber emotes.',

    /**
     * Related to followers
     */
    'cannot_follow_self' => 'A user cannot follow themself.',
    'error_followers' => 'An error occurred retrieving your followers.',
    'follow_not_found' => ':user does not follow :channel',
    'no_followers' => 'You do not have any followers :(',
    'invalid_api_data' => 'Twitch API returned invalid data.',
    'unable_get_following' => 'Unable to get follow data for the specified user.',
    'end_following_list' => 'End of following list.',

    /**
     * Help articles
     */
    'help_articles' => 'Help Articles',
    'help_available_list' => 'List of available help articles with titles: :url',
    'help_no_results' => 'No results found.',

    /**
     * Media (highlights, VODs, uploads)
     */
    'no_highlights' => ':channel has no saved highlights.',
    'no_uploads' => ':channel has no uploaded videos.',
    'no_vods' => ':channel has no available VODs.',
    'invalid_limit_parameter' => 'Invalid "limit" parameter specified. Minimum :min, maximum :max.',
    'invalid_offset_parameter' => 'Invalid "offset" parameter specified. Minimum :min.',
    'end_of_video_list' => 'Reached the end of the video list!',
    'invalid_minutes_parameter' => 'Invalid amount of minutes specified: :min',
    'vodreplay_minutes_too_high' => 'The minutes (:min) specified is longer than the length of the VOD.',

    /**
     * Hosting
     */
    'no_hosts' => 'No one is currently hosting :channel',
    'multiple_hosts' => ':channels and :amount other|:channels and :amount others',

    /**
     * Multi
     */
    'multi_invalid_service' => 'Invalid service specified - Available services: :services',
    'multi_empty_list' => 'You have to specify which streams to create a multi link for (space-separated list).',

    /**
     * Stream
     */
    'stream_offline' => ':channel is offline',
    'stream_get_error' => 'Unable to get stream information for :channel',

    /**
     * Subscriber-related stuff
     */
    'sub_invalid_action' => 'Invalid action specified. Available actions: :actions',
    'sub_needs_authentication' => '%s needs to authenticate to use %sSub (%s sub): %s',
    'sub_count_too_high' => 'Count specified (%d) is higher than the amount of subscribers (%d)',
    'subage_needs_authentication' => '%s needs to authenticate to use subage (Subscription length): %s',
    'subcount_missing_channel' => 'Use ?channel=CHANNEL_NAME or /twitch/subcount/CHANNEL_NAME to get subcount.',
    'subcount_needs_authentication' => '%s needs to authenticate to use subcount: %s',
    'subpoints_missing_channel' => 'Please specify a channel name at the end of the URL - For example: /twitch/subpoints/CHANNEL_NAME',
    'subpoints_needs_authentication' => '%s needs to authenticate to use subpoints: %s',
    'subpoints_generic_error' => 'Unable to retrieve subscriber points for channel: :channel',

    /**
     * Authentication
     */
    'auth_missing_scopes' => 'The OAuth token is missing a required scope(s):',

    /**
     * Teams
     */
    'teams_missing_identifier' => 'Team identifier is empty',

    /**
     * User
     */
    'user_not_found' => 'User not found: :user',

    /**
     * API deprecation
     */
    'api_removed_by_twitch' => '[Deprecated] This API has been removed by Twitch.',
];
