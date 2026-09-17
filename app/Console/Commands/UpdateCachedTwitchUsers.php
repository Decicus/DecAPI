<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\CachedTwitchUser;
use App\User;
use App\Providers\TwitchApiProvider;
use GuzzleHttp\Client;

use App\Repositories\TwitchApiRepository;
use Log;

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
     * @var TwitchApiRepository
     */
    private $api;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TwitchApiRepository $repository)
    {
        parent::__construct();
        $this->api = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $deleteCutoff = Carbon::now()->subDays(3);
        $deletedUsers = CachedTwitchUser::where('created_at', '<', $deleteCutoff)->delete();

        // Only log info message when there is a "large" amount of users deleted.
        if ($deletedUsers > 25) {
            Log::info(sprintf('Deleted %d cached users older than %s', $deletedUsers, $deleteCutoff));
        }

        $users = CachedTwitchUser::where('updated_at', '<', Carbon::now()->subHour(1))->get();
        Log::info(sprintf('Refreshing %d cached users', $users->count()));

        $userChunks = $users->chunk(100);

        $deleted = 0;
        $updated = 0;

        foreach ($userChunks as $chunk)
        {
            $ids = $chunk->pluck('id')->toArray();

            /**
             * To help alleviate the app access token rate limit, we use a user token for the API request instead of the app token.
             * Authenticated user tokens have a separate rate limit bucket from the app access token.
             */
            $tokenUser = User::whereIn('id', $ids)->first();
            if (!empty($tokenUser)) {
                $this->api->setToken($tokenUser);
            }

            $apiUsers = $this->api->usersByIds($ids);

            foreach ($chunk as $cachedUser)
            {
                $id = $cachedUser->id;
                $apiUser = array_filter($apiUsers, function ($user) use ($id) {
                    return $user['id'] === $id;
                });

                /**
                 * Banned, deleted etc.
                 */
                if (empty($apiUser)) {
                    $cachedUser->delete();
                    $deleted++;
                    continue;
                }

                $apiUser = array_shift($apiUser);

                $cachedUser->username = $apiUser['login'];
                $cachedUser->updated_at = Carbon::now();
                $cachedUser->save();
                $updated++;
            }
        }

        Log::info(sprintf('Deleted %d cached users and updated %d cached users', $deleted, $updated));
    }
}
