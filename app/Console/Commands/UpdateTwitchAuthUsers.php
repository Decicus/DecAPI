<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\User;
use Carbon\Carbon;
use Crypt;
use GuzzleHttp\Client;
use Exception;

use App\Http\Resources\Twitch\AuthToken as TwitchAuthToken;

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
     * Twitch API token URL.
     *
     * @var string
     */
    protected $tokenUrl = 'https://id.twitch.tv/oauth2/token';

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
        $timeDiff = Carbon::now();
        $users = User::where('expires', '<', $timeDiff)
                     ->get();

        if ($users->isEmpty()) {
            $this->info('No authenticated Twitch users to check.');
            return 0;
        }

        $settings = [
            'http_errors' => false,
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => env('TWITCH_CLIENT_ID', null),
                'client_secret' => env('TWITCH_CLIENT_SECRET', null),
            ],
        ];

        $client = new Client;

        foreach ($users as $user) {
            $token = Crypt::decrypt($user->refresh_token);
            $settings['form_params']['refresh_token'] = $token;

            $request = $client->request('POST', $this->tokenUrl, $settings);

            $body = json_decode($request->getBody(), true);

            if (isset($body['status']) && $body['status'] === 400) {
                $user->delete();
                $this->info('Deleting user because of invalid refresh token: ' . $user->id);
                continue;
            }

            try {
                $newToken = TwitchAuthToken::make($body)
                                           ->resolve();

                $user->access_token = Crypt::encrypt($newToken['access_token']);
                $user->refresh_token = Crypt::encrypt($newToken['refresh_token']);
                $user->expires = $newToken['expires'];
                $user->save();
                $this->info('Refreshed token for ID: ' . $user->id);
            }
            catch (Exception $ex)
            {
                $this->error('Error occurred refreshing token for ID: ' . $user->id);
            }
        }
    }
}
