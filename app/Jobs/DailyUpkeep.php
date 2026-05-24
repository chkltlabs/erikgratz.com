<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class DailyUpkeep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Payment::where('paid_on', '=', now()->toDateString())
            ->whereIsPaid(false)
            ->update(['is_paid' => true]);

        Artisan::call('fx:refresh');

        Artisan::call('app:simple-fin-intake', ['--start-date' => now()->subDays(14)->toDateString()]);

        ZeroISB::dispatch();
        DebitIFB::dispatch();
        GuessISB::dispatch()->delay(600);
    }
}
