<?php

namespace App\Jobs;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ZeroISBOnDueDate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cards = Card::where('due_date', now()->day)->get();
        foreach ($cards as $card) {
            $card->balance -= $card->interest_saving_balance;
            $card->interest_saving_balance = 0;
            $card->save();
        }
    }
}
