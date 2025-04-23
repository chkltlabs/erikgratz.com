<?php

namespace App\Jobs;

use App\Models\Card;
use Illuminate\Bus\Queueable;
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
        $cards = Card::where('interest_saving_balance', 0)
            ->where('statement_date', now()->day)
            ->get();
        foreach ($cards as $card) {
            $card->interest_saving_balance = $card->balance + $card->pending - $card->interest_free_balance;
            $card->save();
        }
    }
}
