<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\IpBlacklist;
use Artisan;

class AddBlacklistedIp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blacklist:ip {address} {--remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds or removes an IP from the blacklist.';

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
        $shouldRemove = $this->option('remove');
        $ipAddress = $this->argument('address');

        $blacklistIp = IpBlacklist
                ::where('ip_address', $ipAddress)
                ->first();
        $exists = !empty($blacklistIp);

        if ($exists) {
            if ($shouldRemove) {
                $confirmDel = $this->confirm('Are you sure you want to remove this address from the blacklist? Reason: ' . $blacklistIp->reason);

                if (!$confirmDel) {
                    return $this->error('Aborted.');
                }

                $blacklistIp->delete();
                Artisan::call('blacklist:cache');

                return $this->info('Successfully deleted IP from blacklist: ' . $blacklistIp->ip_address);
            }

            return $this->error('This IP address is already blacklisted for reason: ' . $blacklistIp->reason);
        }

        $reason = $this->ask('Reason?');
        IpBlacklist::create([
            'ip_address' => $ipAddress,
            'reason' => $reason,
        ]);

        Artisan::call('blacklist:cache');

        return $this->info(sprintf('Added IP: %s to the blacklist for reason: %s', $ipAddress, $reason));
    }
}
