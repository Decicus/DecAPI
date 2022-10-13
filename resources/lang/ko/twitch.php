<?php

/**
 * Korean translation strings for generic Twitch-related errors.
 * Translated by: Taewook Yang (RelationLife)
 */
return [
    /**
     * Related to chat
     */
    'no_chat_rules' => ':channel 채널에 설정된 채팅 규칙이 없습니다.',
    'error_occurred_chat_clusters' => '채팅 클러스터를 검색하는 도중 오류가 발생했습니다.',
    'error_retrieving_chat_users' => '채널의 사용자를 검색하는 도중 오류가 발생했습니다: ',
    'empty_chat_user_list' => '사용자 목록이 비어 있습니다.',
    'channel_missing_subemotes' => '이 채널에는 구독자 이모티콘이 없습니다.',

    /**
     * Related to followers
     */
    'cannot_follow_self' => '사용자는 자기자신을 팔로우 할 수 없습니다.',
    'error_followers' => ':channel 채널에 대한 팔로어를 검색하는 도중 오류가 발생했습니다.',
    'follow_not_found' => ':user님은 :channel 채널을 팔로우 하지 않습니다.',
    'no_followers' => '팔로워가 없습니다. :(',
    'invalid_api_data' => 'Twitch API가 잘못된 데이터를 반환했습니다.',
    'unable_get_following' => '지정된 사용자에 대한 팔로우 데이터를 가져올 수 없습니다.',
    'end_following_list' => '팔로워 리스트의 마지막입니다.',

    /**
     * Help articles
     */
    'help_articles' => '도움말 문서',
    'help_available_list' => '사용 가능한 도움말 목록: :url',
    'help_no_results' => '검색 결과가 없습니다.',

    /**
     * Media (highlights, VODs, uploads)
     */
    'no_highlights' => ':channel 채널에 저장된 하이라이트가 없습니다.',
    'no_uploads' => ':channel 채널에 업로드 된 영상이 없습니다.',
    'no_vods' => ':channel 채널에 사용 가능한 VOD가 없습니다.',
    'invalid_limit_parameter' => '잘못된 "limit" 매개변수가 지정되었습니다. 최소값 :min, 최대값 :max.',
    'invalid_offset_parameter' => '잘못된 "offset" 매개변수가 지정되었습니다. 최소값 :min.',
    'end_of_video_list' => '영상 목록의 마지막입니다!',
    'invalid_minutes_parameter' => '잘못된 시간변수(분)가 지정되었습니다: :min',
    'vodreplay_minutes_too_high' => '지정된 :min 분이 VOD 길이 보다 깁니다.',

    /**
     * Hosting
     */
    'no_hosts' => '아무도 :channel 채널을 호스팅하고 있지 않습니다.',
    'multiple_hosts' => ':channels 및 :amount',

    /**
     * Multi
     */
    'multi_invalid_service' => '잘못된 서비스가 지정되었습니다. - 사용 가능한 서비스: :services',
    'multi_empty_list' => '다중 링크를 만들 스트림을 지정해야합니다. (스페이스(공백)으로 구분)',

    /**
     * Stream
     */
    'stream_offline' => ':channel 채널은 오프라인입니다.',
    'stream_get_error' => ':channel 에 대한 방송 정보를 가져올 수 없습니다.',

    /**
     * Subscriber-related stuff
     */
    'sub_invalid_action' => '잘못된 작업이 지정되었습니다. 사용 가능한 작업: :actions',
    'sub_needs_authentication' => '%s님이 %sSub (%s sub)을 사용하려면 인증해야 합니다: %s',
    'sub_count_too_high' => '지정한 수(%d)가 구독자 수(%d)보다 큽니다.',
    'subage_needs_authentication' => '%s님이 subage(구독날짜)를 사용하려면 인증해야 합니다: %s',
    'subcount_missing_channel' => '?channel=CHANNEL_NAME를 사용하거나 /twitch/subcount/CHANNEL_NAME을 사용하여 구독자 수를 확인하세요.',
    'subcount_needs_authentication' => '%s님이 subcount를 사용하려면 인증해야 합니다: %s',
    'subcount_generic_error' => ':channel 채널의 구독자 수를 검색할 수 없습니다.',
    'subpoints_missing_channel' => 'URL 끝에 채널 이름을 지정해야 합니다. - 예: /twitch/subpoints/CHANNEL_NAME',
    'subpoints_needs_authentication' => '%s님이 subpoints를 사용하려면 인증해야 합니다: %s',
    'subpoints_generic_error' => ':channel 채널에 대한 정기구독자 포인트를 불러올 수 없습니다.',

    /**
     * Authentication
     */
    'auth_missing_scopes' => 'OAuth 토큰에 필요한 범위가 누락되었습니다:',

    /**
     * Teams
     */
    'teams_missing_identifier' => '팀 식별자가 비어 있습니다.',

    /**
     * User
     */
    'user_not_found' => '유저를 찾을 수 없습니다: :user',

    /**
     * API deprecation
     */
    'api_removed_by_twitch' => '[지원 중단] 이 API는 Twitch에 의해 제거되었습니다.',
];
