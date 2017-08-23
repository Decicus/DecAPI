<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\User;
use Carbon\Carbon;
use Crypt;
use GuzzleHttp\Client;

class UpdateTwitchAuthUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch:authuserupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the users table to make sure all tokens are valid, removes if invalid.';

    /**
     * Twitch API base URL.
     *
     * @var string
     */
    protected $baseUrl = 'https://api.twitch.tv/kraken';

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
        $timeDiff = Carbon::now()->subHours(24);
        $users = User::where('updated_at', '<', $timeDiff)
                ->get();

        if ($users->isEmpty()) {
            return $this->info('No authenticated Twitch users to check.');
        }

        $settings = [
            'headers' => [
                'Accept' => 'application/vnd.twitchtv.v5+json',
                'Client-ID' => env('TWITCH_CLIENT_ID'),
            ],
            'http_errors' => false,
        ];

        $client = new Client;

        foreach ($users as $user) {
            $token = Crypt::decrypt($user->access_token);
            $settings['headers']['Authorization'] = 'OAuth ' . $token;
            $request = $client->request('GET', $this->baseUrl, $settings);

            $body = json_decode($request->getBody(), true);

            if (empty($body['token']) || $body['token']['valid'] === false) {
                $user->delete();

                if (empty($user->twitch)) {
                    return $this->info(sprintf('Removed user: %s', $user->id));
                }

                return $this->info(sprintf('Removed user: %s (%s)', $user->twitch->username, $user->id));
            }

            $user->updated_at = Carbon::now();
            $user->save();
        }
    }
}
