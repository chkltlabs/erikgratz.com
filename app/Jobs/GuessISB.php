<?php

namespace App\Jobs;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GuessISB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $runDate = now()->day;

        $rangeDate = now()->subDays(15)->day;

        $lesser = min($rangeDate, $runDate);
        $greater = max($rangeDate, $runDate);

        $cards = Card::whereBetween('due_date', [$lesser, $greater])->where('interest_saving_balance', 0)->get();
        foreach ($cards as $card) {
            $card->interest_saving_balance = $card->balance + $card->pending - $card->interest_free_balance;
            $card->save();
        }
    }
}
