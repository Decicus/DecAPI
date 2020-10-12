<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\RateLimitApiKeys as ApiKey;

class SetRateLimitApiKeyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratelimit:set {key} {--enable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the current enabled/disabled status for an API key. By default it disables it. Use --enable to enable.';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $enable = $this->option('enable');
        $key = $this->argument('key');

        $column = is_numeric($key) ? 'id' : 'api_key';

        $apiKeyModel = ApiKey
                ::where($column, $key)
                ->first();

        if (empty($apiKeyModel)) {
            $this->error('The specified API key does not exist: ' . $key);
            return 1;
        }

        $apiKeyModel->enabled = $enable;
        $apiKeyModel->save();
        $this->info(sprintf('The API key %s has been %s', $key, $enable ? 'enabled' : 'disabled'));
    }
}
