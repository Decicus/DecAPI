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
        Commands\ImportSubcountTokens::class,
        Commands\UpdateCachedTwitchUsers::class,
        Commands\UpdateTwitchAuthUsers::class,
        Commands\UpdateTwitchHelp::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('twitch:userupdate')
                 ->everyMinute();

        $schedule->command('twitch:help')
                 ->daily();

        $schedule->command('twitch:authuserupdate')
                ->hourly()
                ->when(function() {
                    return date('G') % 6 === 0;
                });
    }
}
