<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;
use App\RateLimitApiKeys;

class GenerateNewRateLimitApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratelimit:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a new API key that will bypass the rate limits applied to routes.';

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
        $name = $this->ask('Name/title of the API key?');
        $description = $this->ask('Additional description for the API key?');

        $this->info('Name/title: ' . $name);
        $this->info('Description: ' . $description);

        $confirm = $this->confirm('Is the information provided correct?');
        if (!$confirm) {
            return $this->error('Aborted.');
        }

        $generatedApiKey = Uuid::uuid4()->toString();

        $apiKeyModel = RateLimitApiKeys::create([
            'name' => $name,
            'description' => $description,
            'enabled' => true,
            'api_key' => $generatedApiKey,
        ]);

        $this->info('Created a new API key for bypassing rate limits: ' . $generatedApiKey);
    }
}
