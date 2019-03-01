<?php

/**
 * Deutsche Übersetzung für generelle Twitch bezogene Fehler.
 */
return [
    /**
     * Chatbezogen
     */
    'no_chat_rules' => ':channel hat keine Chatregeln definiert.',
    'error_occurred_chat_clusters' => 'Fehler beim Empfangen der Chatcluster.',
    'error_retrieving_chat_users' => 'Fehler beim Empfangen der Benutzer für: ',
    'empty_chat_user_list' => 'Die Benutzerliste ist leer.',
    'channel_missing_subemotes' => 'Für diesen Kanal existieren keine Abonnement Emotes.',

    /**
     * Followerbezogen
     */
    'cannot_follow_self' => 'Man kann sich nicht selbst folgen.',
    'error_followers' => 'Fehler beim Empfangen der Follower aufgetreten.',
    'no_followers' => 'Du hast keine Follower :(',
    'invalid_api_data' => 'Twitch API gab falsche Daten zurück.',
    'unable_get_following' => 'Followerdaten für den angegebenen Nutzer konnten nicht abgerufen werden.',
    'end_following_list' => 'Ende der Followerliste.',

    /**
     * Hilfsartikel
     */
    'help_articles' => 'Hilfsartikel',
    'help_available_list' => 'Liste mit verfügbaren Hilfsartikeln inklusive Titel: :url',
    'help_no_results' => 'Keine Ergebnisse gefunden.',

    /**
     * Medien (Highlights, VODs, hochgeladene Videos)
     */
    'no_highlights' => ':channel hat keine gespeicherten Highlights.',
    'no_uploads' => ':channel hat keine hochgeladenen Videos.',
    'no_vods' => ':channel hat keine verfügbaren VODs.',
    'invalid_limit_parameter' => 'Falscher "limit" Parameter angegeben. Minimal :min, Maximal :max.',
    'invalid_offset_parameter' => 'Falscher "offset" Parameter angegeben. Minimal :min.',
    'end_of_video_list' => 'Ende der Videoliste erreicht!',
    'invalid_minutes_parameter' => 'Falsche Anzahl an Minuten angegeben: :min',
    'vodreplay_minutes_too_high' => 'Die angegebenen Minuten (:min) sind länger als das VOD selbst.',

    /**
     * Hosting
     */
    'no_hosts' => 'Aktuell wird :channel von niemandem gehostet.',
    'multiple_hosts' => ':channels und :amount weiterer|:channels und :amount weitere',

    /**
     * Multi
     */
    'multi_invalid_service' => 'Falscher Dienst angegeben - Verfügbare Dienste: :services',
    'multi_empty_list' => 'Du musst eine (mit Leerzeichen geteilte) Liste an Kanälen angeben für die du einen Multi-Link generieren willst.',

    /**
     * Abonnent-bezogenes Zeug
     */
    'sub_invalid_action' => 'Falsche Aktion angegeben. Verfügbare Aktionen: :actions',
    'sub_needs_authentication' => '%s muss sich authentifizieren um %sSub zu nutzen (%s sub): %s',
    'sub_count_too_high' => 'Angegebene Anzahl (%d) ist höher als die Anzahl der Abonnementen (%d)',
    'subage_needs_authentication' => '%s muss sich authentifizieren um subage zu nutzen (Dauer des Abonnements): %s',
    'subcount_missing_channel' => 'Nutze ?channel=KANALNAME oder /twitch/subcount/KANALNAME um die Anzahl der Abonnenten zu erhalten.',
    'subcount_needs_authentication' => '%s muss sich authentifizieren um subcount zu nutzen: %s',
    'subpoints_missing_channel' => 'Bitte einen Kanalnamen am Ende der URL angeben - Beispiel: /twitch/subpoints/KANALNAME',
    'subpoints_needs_authentication' => '%s muss sich authentifizieren um subpoints zu nutzen: %s',

    /**
     * Authentifizierung
     */
    'auth_missing_scopes' => 'Beim OAuth Token fehlen folgende Scopes:',

    /**
     * Teams
     */
    'teams_missing_identifier' => 'Identifikator Team ist leer',
];
