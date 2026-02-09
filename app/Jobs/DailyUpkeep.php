<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\User;
use App\Services\SimpleFin\SimpleFinIntakeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        User::whereNotNull('simple_fin_url')->each(function (User $user) {
            try {
                SimpleFinIntakeService::fetchAndIntake($user, now()->subDays(14));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("SimpleFIN Intake failed for user {$user->id}: " . $e->getMessage());
            }
        });

        ZeroISB::dispatch();
        DebitIFB::dispatch();
        GuessISB::dispatch()->delay(600);
    }
}
