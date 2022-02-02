<?php

/**
 * Dutch translation strings for generic Twitch-related errors.
 */
return [
    /**
     * Related to chat
     */
    'no_chat_rules' => ':channel heeft geen chatregels.',
    'error_occurred_chat_clusters' => 'Er is een fout opgetreden bij het ophalven van chat clusters.',
    'error_retrieving_chat_users' => 'Er is een fout opgetreden bij het ophalen van de gebruikers voor kanaal: ',
    'empty_chat_user_list' => 'De lijst gebruikers is leeg.',
    'channel_missing_subemotes' => 'Dit kanaal heeft geen subscriber emotes.',

    /**
     * Related to followers
     */
    'cannot_follow_self' => 'Een gebruiker kan zichzel niet volgen.',
    'error_followers' => 'Er is een fout opgetreden bij het ophalen van je volgers.',
    'follow_not_found' => ':user volgt :channel niet!',
    'no_followers' => 'Je hebt nog geen volgers :(',
    'invalid_api_data' => 'De Twitch API gaf invalide data terug.',
    'unable_get_following' => 'Het is niet mogelijk de followdatum voor de gebruiker op te halen.',
    'end_following_list' => 'Einde van de volgers lijst.',

    /**
     * Help articles
     */
    'help_articles' => 'Help Artikelen',
    'help_available_list' => 'Lijst van beschikbare help artikelen met titels: :url',
    'help_no_results' => 'Geen resultaten gevonden.',

    /**
     * Media (highlights, VODs, uploads)
     */
    'no_highlights' => ':channel heeft geen opgeslagen highlights.',
    'no_uploads' => ':channel heeft geen geuploade videos.',
    'no_vods' => ':channel heeft geen beschikbare VODs.',
    'invalid_limit_parameter' => 'Invalide "limit" parameter gespecificeerd. Minimum :min, maximum :max.',
    'invalid_offset_parameter' => 'Invalide "offset" parameter gespecificeerd. Minimum :min.',
    'end_of_video_list' => 'Einde van de video lijst bereikt!',
    'invalid_minutes_parameter' => 'Invalide aantal minuten gespecificeerd: :min',
    'vodreplay_minutes_too_high' => 'De gespecificeerde minuten (:min) zijn langer dan de VOD is.',

    /**
     * Hosting
     */
    'no_hosts' => 'Niemand host momenteel :channel',
    'multiple_hosts' => ':channels en :amount andere|:channels en :amount andere',

    /**
     * Multi
     */
    'multi_invalid_service' => 'Invalide service gespecificeerd - Beschikbare services: :services',
    'multi_empty_list' => 'Je moet specificeren voor welke streams je een multilink wil maken (space-separated list).',

    /**
     * Stream
     */
    'stream_offline' => ':channel is offline',
    'stream_get_error' => 'Kan de streaminformatie niet ophalen voor :channel',

    /**
     * Subscriber-related stuff
     */
    'sub_invalid_action' => 'Invalide actie gespecificeerd. Beschikbare acties: :actions',
    'sub_needs_authentication' => '%s moet authenticeren om %sSub (%s sub): %s te gebruiken.',
    'sub_count_too_high' => 'De gespecificeerde count (%d) is hoger dan het aantal subscribers (%d)',
    'subage_needs_authentication' => '%s moet authenticeren om subage te gebruiken (Subscription length): %s',
    'subcount_missing_channel' => 'Gebruik ?channel=CHANNEL_NAME of /twitch/subcount/CHANNEL_NAME om de subcount op te halen.',
    'subcount_needs_authentication' => '%s moet authenticeren om subcount te gebruiken: %s',
    'subcount_generic_error' => 'Het ophalen van de subscriber count is mislukt voor kanaal: :channel',
    'subpoints_missing_channel' => 'Specificeer de kanaal naam aan het einde van de URL - Voorbeeld: /twitch/subpoints/CHANNEL_NAME',
    'subpoints_needs_authentication' => '%s moet authenticeren om subpoints te gebruiken: %s',
    'subpoints_generic_error' => 'Het ophalen van de subscriber points is mislukt voor kanaal: :channel',

    /**
     * Authentication
     */
    'auth_missing_scopes' => 'Je OAuth token mist een verplichte scope(s):',

    /**
     * Teams
     */
    'teams_missing_identifier' => 'De Team identifier is leeg',

    /**
     * User
     */
    'user_not_found' => 'Gebruiker niet gevonden: :user',

    /**
     * API deprecation
     */
    'api_removed_by_twitch' => '[Deprecated] Deze API is verwijderd door Twitch.',
];
