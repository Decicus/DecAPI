<?php

/**
 * Russian translation strings for generic Twitch-related errors.
 * done by paws of kriper1111
 */
return [
    /**
     * Related to chat
     */
    'no_chat_rules' => 'На канале :channel не установлено никаких правил.',
    'error_occurred_chat_clusters' => 'Произошла ошибка при получении кластеров чата.',
    'error_retrieving_chat_users' => 'Произошла ошибка при получении пользователей канала channel: ',
    'empty_chat_user_list' => 'Список пользователей пуст.',
    'channel_missing_subemotes' => 'На этом канале нету смайлов сабскрайберов.',

    /**
     * Related to followers
     */
    'cannot_follow_self' => 'Пользователь не может фолловить себя.',
    'error_followers' => 'Произошла ошибка при получении списка ваших фолловеров.',
    'follow_not_found' => ':user не зафолловлен на :channel',
    'no_followers' => 'У вас нету подписчиков :(',
    'invalid_api_data' => 'Twitch API вернул неправильные данные.',
    'unable_get_following' => 'Невозможно получить данные о фолловерах указанного пользователя.',
    'end_following_list' => 'Конец списка фоллова.',

    /**
     * Help articles
     */
    'help_articles' => 'Справка',
    'help_available_list' => 'Список доступных страниц справки по запросу: :url',
    'help_no_results' => 'Результаты не найдены.',

    /**
     * Media (highlights, VODs, uploads)
     */
    'no_highlights' => 'На канале :channel нету сохранённых хайлайтов.',
    'no_uploads' => 'На канале :channel нету загруженных видео.',
    'no_vods' => 'На канале :channel нету доступных VOD'ов.',
    'invalid_limit_parameter' => 'Указано неверное значение "limit". Минимум :min, максимум :max.',
    'invalid_offset_parameter' => 'Указано неверное значение "offset". Минимум :min.',
    'end_of_video_list' => 'Достигнут конец списка видео!',
    'invalid_minutes_parameter' => 'Указано неверное количество минут: :min',
    'vodreplay_minutes_too_high' => 'Указанное время (:min) больше длины VOD'а.',

    /**
     * Hosting
     */
    'no_hosts' => 'Никто не хостит канал :channel',
    'multiple_hosts' => ':channels и :amount другой|:channels и :amount других',

    /**
     * Multi
     */
    'multi_invalid_service' => 'Указан неверный сервис - Возможные варианты: :services',
    'multi_empty_list' => 'Укажите стримы для создания мульти-ссылки (список разделенный пробелами).',

    /**
     * Stream
     */
    'stream_offline' => ':channel оффлайн',
    'stream_get_error' => 'Не удалось получить информацию стрима о канале :channel',

    /**
     * Subscriber-related stuff
     */
    'sub_invalid_action' => 'Указано неверное действие. Доступные варианты: :actions',
    'sub_needs_authentication' => '%s должен авторизоваться чтобы использовать %sSub (саб %s): %s',
    'sub_count_too_high' => 'Указанное значение (%d) выше количества сабскрайберов (%d)',
    'subage_needs_authentication' => '%s должен авторизоваться чтобы использовать subage (длину подписки): %s',
    'subcount_missing_channel' => 'Используйте ?channel=ИМЯ_КАНАЛА или /twitch/subcount/ИМЯ_КАНАЛА чтобы получить количество сабов.',
    'subcount_needs_authentication' => '%s должен авторизоваться чтобы посчитать сабов: %s',
    'subpoints_missing_channel' => 'Пожалуйста, укажите имя канала в конце ссылки - Например: /twitch/subpoints/ИМЯ_КАНАЛА',
    'subpoints_needs_authentication' => '%s должен авторизоваться чтобы посчитать саб-поинты: %s',

    /**
     * Authentication
     */
    'auth_missing_scopes' => 'Токен OAuth не имеет требуемых scope(-ов):',

    /**
     * Teams
     */
    'teams_missing_identifier' => 'Идентификатор команды пуст.',

    /**
     * User
     */
    'user_not_found' => 'Пользователь не найден: :user',
];
