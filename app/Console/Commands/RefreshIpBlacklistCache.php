<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\IpBlacklist;
use Cache;

class RefreshIpBlacklistCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blacklist:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the cache with the current IP blacklist';

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
        $blacklist = IpBlacklist::all();
        Cache::put('ip_blacklist', $blacklist);
    }
}
