<?php

namespace App\Console\Commands;

use App\Exceptions\TwitchApiException;
use Illuminate\Console\Command;

use App\User;
use Carbon\Carbon;
use Crypt;
use GuzzleHttp\Client;
use Exception;
use Log;

use App\Repositories\TwitchApiRepository;

class ValidateTwitchAuthUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch:authuservalidate';

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
    protected $validateUrl = 'https://id.twitch.tv/oauth2/validate';

    /**
     * An instance of the Twitch API repository.
     *
     * @var \App\Repositories\TwitchApiRepository
     */
    protected $api;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TwitchApiRepository $repository)
    {
        $this->api = $repository;

        parent::__construct();
    }

    /**
     * Refreshes all tokens that need to be refreshed.
     * Any users that are refreshed will be returned in an array, as they are implicitly validated due to the refreshing process.
     *
     * @return void
     */
    private function refreshTokens()
    {
        $timeDiff = Carbon::now();
        $users = User::where('expires', '<', $timeDiff)
                     ->get();

        if ($users->isEmpty()) {
            return [];
        }

        $userIds = [];
        foreach ($users as $user)
        {
            try {
                $this->api->setToken($user);
                $userIds[] = $user->id;
                Log::info('Refreshed token for ID: ' . $user->id);
            }
            catch (TwitchApiException $ex)
            {
                if ($ex->getCode() === 401) {
                    Log::info('Deleting user because of invalid access token: ' . $user->id);
                    $user->delete();
                }
            }
            catch (Exception $ex)
            {
                Log::error('Error occurred refreshing token for ID: ' . $user->id);
                Log::error($ex->getMessage());
                Log::error($ex->getTraceAsString());
            }
        }

        return $userIds;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $refreshedUsers = $this->refreshTokens();

        $settings = [
            'http_errors' => false,
            'headers' => [],
        ];

        $client = new Client;

        $users = User::whereNotIn('id', $refreshedUsers)
                     ->get();

        foreach ($users as $user) {
            $user = User::lockForUpdate()
                        ->find($user->id);

            $token = Crypt::decrypt($user->access_token);
            $settings['headers']['Authorization'] = sprintf('OAuth %s', $token);
            $request = $client->request('GET', $this->validateUrl, $settings);

            if ($request->getStatusCode() !== 200) {
                $user->delete();
                Log::info('Deleting user because of invalid access token: ' . $user->id);
                continue;
            }

            Log::info('User token is valid: ' . $user->id);
            // Token is valid, unlock record and continue.
            $user->save();
        }
    }
}
