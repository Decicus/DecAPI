<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\RateLimitApiKeys as ApiKey;

class ListRateLimitApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratelimit:list {key?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the current API keys that are allowed to bypass the rate limit.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name of the headers displayed on output table.
     *
     * @var array
     */
    private $tableHeaders = ['ID', 'Name', 'Description', 'API Key', 'Enabled'];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $apiKey = $this->argument('key');
        if (empty($apiKey)) {
            $keys = ApiKey
                    ::all()
                    ->toArray();

            $this->table($this->tableHeaders, $keys);

            return 0;
        }

        $apiKey = trim($apiKey);

        if (is_numeric($apiKey)) {
            $apiKeyModel = ApiKey::where('id', $apiKey)->get();
        } else {
            $apiKeyModel = ApiKey::where('api_key', $apiKey)->get();
        }


        if ($apiKeyModel->isEmpty()) {
            $this->error('The specified API key does not exist: ' . $apiKey);
            return 1;
        }

        $this->table($this->tableHeaders, $apiKeyModel->toArray());
    }
}
