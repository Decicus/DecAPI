<?php

/**
 * Turkish translation strings for generic Twitch-related errors.
 */
return [
    /**
     * Related to chat
     */
    'no_chat_rules' => ':channel kanalında herhangi bir kural yok.',
    'error_occurred_chat_clusters' => 'Sohbet kümeleri alınırken bir hata oluştu.',
    'error_retrieving_chat_users' => 'channel: kullanıcıları alınırken hata oluştur ',
    'empty_chat_user_list' => 'Kullanıcı listesi boş.',
    'channel_missing_subemotes' => 'Bu kanalda herhangi bir abone ifadesi yok.',

    /**
     * Related to followers
     */
    'cannot_follow_self' => 'Bir kullanıcı kendisini takip edemez.',
    'error_followers' => 'Takipçilerinizi alınırken bir hata oluştu.',
    'follow_not_found' => ':user, :channel Kanalını takip etmiyor',
    'no_followers' => 'Hiç takipçiniz yok :(',
    'invalid_api_data' => 'Twitch API geçersiz veri döndürdü.',
    'unable_get_following' => 'Belirtilen kullanıcı için takip bilgisi alınamıyor.',
    'end_following_list' => 'Takip listesinin sonu.',

    /**
     * Help articles
     */
    'help_articles' => 'Yardım Makaleleri',
    'help_available_list' => 'Başlıkları ile birlikte yardım makalelerinin listesi: :url',
    'help_no_results' => 'Sonuç bulunamadı.',

    /**
     * Media (highlights, VODs, uploads)
     */
    'no_highlights' => ':channel kanalında hiç highlight yok.',
    'no_uploads' => ':channel kanalında hiç yüklenmiş video yok.',
    'no_vods' => ':channel kanalında mevcut klip yok.',
    'invalid_limit_parameter' => 'Geçersiz "limit" parametresi tanımlandı. Minimum :min, maksimum :max.',
    'invalid_offset_parameter' => 'Geçersiz "offset" parametresi tanımlandı. Minimum :min.',
    'end_of_video_list' => 'Video listesinin sonuna gelindi!',
    'invalid_minutes_parameter' => 'Geçersiz dakika tanımlandı: :min',
    'vodreplay_minutes_too_high' => '(:min) dakika klibin uzunluğundan daha fazla.',

    /**
     * Hosting
     */
    'no_hosts' => 'Şu anda :channel kanalın kimse sunmuyor.',
    'multiple_hosts' => ':channels ve diğer :amount|:channels ve diğer :amount',

    /**
     * Multi
     */
    'multi_invalid_service' => 'Geçersiz servis belirtildi - Mevcut servisler: :services',
    'multi_empty_list' => 'Multi link yayınları belirtmelisiniz (Boşluk ile ayrılmış liste).',

    /**
     * Stream
     */
    'stream_offline' => ':channel çevrimdışı',
    'stream_get_error' => ':channel için yayın bilgileri alınamıyor',

    /**
     * Subscriber-related stuff
     */
    'sub_invalid_action' => 'Geçersiz işlem belirtildi. Mevcut işlemler: :actions',
    'sub_needs_authentication' => '%s şunları kullanmak için kimlik doğrulaması gerekli; %sSub (%s sub): %s',
    'sub_count_too_high' => '(%d) miktarı mevcut abone miktarından fazla (%d)',
    'subage_needs_authentication' => '%s subage kullanmak için kimlik doğrulaması gerekli (Abonelik süresi): %s',
    'subcount_missing_channel' => 'Subcount almak için ?channel=KANAL_ADI ya da /twitch/subcount/KANAL_ADI şeklinde kullanın.',
    'subcount_needs_authentication' => '%s subcount kullanmak için kimlik doğrulaması gerekli: %s',
    'subpoints_missing_channel' => 'Lütfen URL\'in sonunda bir kanal adı belirtin. - Örneğin: /twitch/subpoints/KANAL_ADI',
    'subpoints_needs_authentication' => '%s subpoints kullanmak için kimlik doğrulaması gerekli: %s',
    'subpoints_generic_error' => 'Kanal için abone puanları alınamıyor: :channel',

    /**
     * Authentication
     */
    'auth_missing_scopes' => 'OAuth anahtarı bulunamadı gerekli kapsam(lar):',

    /**
     * Teams
     */
    'teams_missing_identifier' => 'Takım tanımlayıcısı bulunamadı',

    /**
     * User
     */
    'user_not_found' => 'Kullanıcı bulunamadı: :user',
];
