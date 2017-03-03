<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\CachedTwitchUser;
use GuzzleHttp\Client;

class UpdateCachedTwitchUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch:userupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the cached Twitch users table.';

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
        $users = CachedTwitchUser::where('updated_at', '<', Carbon::now()->subHours(1))->get();
        $client = new Client;
        $settings = [
            'headers' => [
                'Accept' => 'application/vnd.twitchtv.v5+json',
                'Client-ID' => env('TWITCH_CLIENT_ID')
            ],
            'http_errors' => false
        ];

        foreach ($users as $user) {
            $request = $client->request('GET', 'https://api.twitch.tv/kraken/users?login=' . $user->username, $settings);

            $body = json_decode($request->getBody(), true);
            if ($request->getStatusCode() !== 200) {
                $this->info($body['status'] . ' - ' . $body['message']);
                continue;
            }

            if (empty($body['users'])) {
                $user->delete();
                continue;
            }

            $apiUser = $body['users'][0];

            if ($user->id !== $apiUser['_id']) {
                $user->id = $apiUser['_id'];
                $user->save();
                continue;
            }

            $user->updated_at = Carbon::now();
            $user->save();
        }
    }
}
