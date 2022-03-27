<?php

/**
 * Portuguese (Brazil) translation strings for generic Twitch-related errors.
 */
return [
    /**
     * Related to chat
     */
    'no_chat_rules' => ':channel não tem regras de bate-papo definidas.',
    'error_occurred_chat_clusters' => 'Ocorreu um erro ao recuperar os clusters do bate-papo.',
    'error_retrieving_chat_users' => 'Ocorreu um erro ao recuperar os usuários do canal: ',
    'empty_chat_user_list' => 'A lista de usuários está vazia.',
    'channel_missing_subemotes' => 'Este canal não tem emotes de inscritos.',

    /**
     * Related to followers
     */
    'cannot_follow_self' => 'Um usuário não pode seguir ele mesmo.',
    'error_followers' => 'Ocorreu um erro ao recuperar os seguidores de :channel',
    'follow_not_found' => ':user não segue :channel',
    'no_followers' => 'Você não tem seguidores :(',
    'invalid_api_data' => 'A API da Twitch retornou dados inválidos.',
    'unable_get_following' => 'Não foi possível obter os dados de seguidores do usuário especificado.',
    'end_following_list' => 'Fim da lista de seguidores.',

    /**
     * Help articles
     */
    'help_articles' => 'Artigos de ajuda',
    'help_available_list' => 'Lista de artigos de ajuda disponíveis com títulos: :url',
    'help_no_results' => 'Nenhum resultado encontrado.',

    /**
     * Media (highlights, VODs, uploads)
     */
    'no_highlights' => ':channel não tem destaques salvos.',
    'no_uploads' => ':channel não tem vídeos enviados.',
    'no_vods' => ':channel não tem VODs disponíveis.',
    'invalid_limit_parameter' => 'Parâmetro "limit" inválido especificado. Mínimo :min, máximo :max.',
    'invalid_offset_parameter' => 'Parâmetro "offset" inválido especificado. Mínimo :min.',
    'end_of_video_list' => 'Chegou ao fim da lista de vídeos!',
    'invalid_minutes_parameter' => 'Quantidade inválida de minutos especificada: :min',
    'vodreplay_minutes_too_high' => 'Os minutos (:min) especificados são maiores que a duração do VOD.',

    /**
     * Hosting
     */
    'no_hosts' => 'Ninguém está hospedando :channel no momento',
    'multiple_hosts' => ':channels e outros :amount|:channels e outros :amount',

    /**
     * Multi
     */
    'multi_invalid_service' => 'Serviço inválido especificado - Serviços disponíveis: :services',
    'multi_empty_list' => 'Você precisa especificar para quais transmissões criar um link múltiplo (lista separada por espaço).',

    /**
     * Stream
     */
    'stream_offline' => ':channel está offline',
    'stream_get_error' => 'Não foi possível obter informações da transmissão de :channel',

    /**
     * Subscriber-related stuff
     */
    'sub_invalid_action' => 'Ação inválida especificada. Ações disponíveis: :actions',
    'sub_needs_authentication' => '%s precisa se autenticar para usar %sSub (%s sub): %s',
    'sub_count_too_high' => 'A contagem especificada (%d) é maior que a quantidade de assinantes (%d)',
    'subage_needs_authentication' => '%s precisa se autenticar para usar subage (tempo de inscrição): %s',
    'subcount_missing_channel' => 'Use ?channel=CHANNEL_NAME ou /twitch/subcount/CHANNEL_NAME para obter a contagem de inscritos.',
    'subcount_needs_authentication' => '%s precisa se autenticar para usar subcount (contagem de inscritos): %s',
    'subcount_generic_error' => 'Não foi possível recuperar a contagem de inscritos para o canal: :channel',
    'subpoints_missing_channel' => 'Especifique um nome de canal no final da URL - Por exemplo: /twitch/subpoints/CHANNEL_NAME',
    'subpoints_needs_authentication' => '%s precisa se autenticar para usar subpoints (pontos de inscritos): %s',
    'subpoints_generic_error' => 'Não foi possível obter os pontos de inscritos para o canal: :channel',

    /**
     * Authentication
     */
    'auth_missing_scopes' => 'O token OAuth não tem escopo(s) requerido(s):',

    /**
     * Teams
     */
    'teams_missing_identifier' => 'O identificador da equipe está vazio',

    /**
     * User
     */
    'user_not_found' => 'Usuário não encontrado: :user',

    /**
     * API deprecation
     */
    'api_removed_by_twitch' => '[Descontinuado] Esta API foi removida pela Twitch.',
];
