<?php

namespace App\Repositories;

use App\Services\TwitchApiClient;

class TwitchApiRepository
{
    /**
     * @var App\Services\TwitchApiClient
     */
    private $client;

    public function __construct(TwitchApiClient $client)
    {
        $this->client = $client;
    }
}
