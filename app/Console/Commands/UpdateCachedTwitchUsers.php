<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\CachedTwitchUser;
use GuzzleHttp\Client;

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
        $oneMonthAgo = Carbon::now()->subMonths(1);
        $deletedUsers = CachedTwitchUser::where('created_at', '<', $oneMonthAgo)->delete();

        if ($deletedUsers > 0) {
            Log::info(sprintf('Deleted %d cached users older than %s', $deletedUsers, $oneMonthAgo));
        }

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
            $request = $client->request('GET', 'https://api.twitch.tv/kraken/users/' . $user->id, $settings);

            $body = json_decode($request->getBody(), true);
            $status = $request->getStatusCode();
            if ($status !== 200) {
                Log::info($body['status'] . ' - ' . $body['message']);

                // Delete banned/deleted/non-existing users.
                if ($status === 422 || $status === 404) {
                    Log::info('Deleting user: ' . $user->id);
                    $user->delete();
                }

                continue;
            }

            if ($user->username !== $body['name']) {
                $user->username = $body['name'];
                $user->save();
                continue;
            }

            $user->updated_at = Carbon::now();
            $user->save();
        }
    }
}
