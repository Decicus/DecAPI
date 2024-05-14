<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AddBlacklistedIp::class,
        Commands\DecryptString::class,
        Commands\GenerateNewRateLimitApiKey::class,
        Commands\ImportSubcountTokens::class,
        Commands\ListRateLimitApiKeys::class,
        Commands\RefreshIpBlacklistCache::class,
        Commands\RefreshIzurviveLocations::class,
        Commands\SetRateLimitApiKeyStatus::class,
        Commands\UpdateCachedTwitchUsers::class,
        Commands\ValidateTwitchAuthUsers::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Cached Twitch username => ID mappings
        $schedule->command('twitch:userupdate')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping(60);

        // Authenticated channels for subcount/subpoints/subage etc.
        $schedule->command('twitch:authuservalidate')
                 ->twiceDaily()
                 ->withoutOverlapping();
    }
}
