<?php

namespace App\Console;

use App\Jobs\DailyUpkeep;
use App\Jobs\HourlyUpkeep;
use App\Models\StateDump;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('cache:prune-stale-tags')->hourly();
        $schedule->command('telescope:prune')->daily();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->job(HourlyUpkeep::class)->hourly();
        $schedule->job(DailyUpkeep::class)->dailyAt('20:00');
        $schedule->command('fx:refresh')->dailyAt('17:30');
        $schedule->call(fn () => StateDump::checkShouldDump())->dailyAt('23:50');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
