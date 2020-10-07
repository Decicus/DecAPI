<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use GuzzleHttp\Client;
use Crypt;
use App\User;

class ImportSubcountTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decapi:subcount {token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all the old OAuth tokens in the `subcount` table to the new users table.';

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
        $apiToken = $this->argument('token');
        $url = 'https://old.decapi.me/twitch/subcount_api?api_token=' . $apiToken;

        $client = new Client;
        $request = $client->request('GET', $url, ['http_errors' => false]);

        $data = json_decode($request->getBody(), true);
        if ($data['success'] !== true) {
            $this->error($data['error']);
            return 1;
        }

        $users = $data['users'];
        $progress = $this->output->createProgressBar(count($users));
        foreach ($users as $name => $token) {
            $progress->advance();
            $tokenCheck = $client->request('GET', 'https://api.twitch.tv/kraken', [
                'headers' => [
                    'Authorization' => 'OAuth ' . $token,
                    'Accept' => 'application/vnd.twitchtv.v5+json',
                    'Client-ID' => env('TWITCH_CLIENT_ID')
                ],
                'http_errors' => false
            ]);

            $check = json_decode($tokenCheck->getBody(), true);
            $info = $check['token'];
            if ($info['valid'] !== true) {
                continue;
            }

            $username = $info['user_name'];
            $scopes = implode('+', $info['authorization']['scopes']);
            $userId = $info['user_id'];

            $user = User::where(['id' => $userId])->first();

            if (empty($user)) {
                User::create([
                    'id' => $userId,
                    'username' => $username,
                    'access_token' => Crypt::encrypt($token),
                    'scopes' => $scopes
                ]);
            }
        }

        $progress->finish();

        $this->info('Successfully imported all users.');
    }
}
