<?php

namespace App\Jobs;

use App\Services\TravelWallet\PromoIngestor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestTravelPromos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PromoIngestor $ingestor): void
    {
        $ingestor->ingestFeeds();
    }
}
