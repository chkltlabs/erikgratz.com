<?php

namespace App\Jobs;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DebitIFBOnFirst implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cards = Card::where('interest_free_balance', '>', 0)->get();
        foreach ($cards as $card) {
            $card->interest_free_balance -= $card->interest_free_balance_payment;
            if ($card->interest_free_balance <= 0) {
                $card->interest_free_balance = 0;
                $card->interest_free_balance_payment = 0;
            }
            $card->save();
        }
    }
}
