<?php

namespace App\Console;

use App\Jobs\DebitIFBOnFirst;
use App\Jobs\GuessISB;
use App\Jobs\ZeroISBOnDueDate;
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
        $schedule->job(ZeroISBOnDueDate::class)->dailyAt('20:00');
        $schedule->job(DebitIFBOnFirst::class)->monthlyOn(1, '20:00');
        $schedule->job(GuessISB::class)->monthlyOn(1, '20:10');
        $schedule->job(GuessISB::class)->monthlyOn(15, '20:10');
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
