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

    /**
     * Related to followers
     */
    'cannot_follow_self' => 'A user cannot follow themself.',
    'error_followers' => 'An error occurred retrieving your followers.',
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
     * Subscriber-related stuff
     */
    'sub_invalid_action' => 'Invalid action specified. Available actions: :actions',
];
