<?php

declare(strict_types=1);

namespace App\Services\TravelWallet\PromoSources;

use Illuminate\Support\Facades\Http;
use SimplePie\SimplePie;

class DoctorOfCreditRssSource implements PromoSource
{
    public function key(): string
    {
        return 'doctor_of_credit';
    }

    public function fetch(): array
    {
        $url = (string) config('travel-wallet.promo.doctor_of_credit_rss');
        $response = Http::timeout(20)->get($url);
        $response->throw();

        return $this->parse((string) $response->body());
    }

    /**
     * @return list<array{external_id: string, title: string, snippet: string}>
     */
    public function parse(string $xml): array
    {
        $feed = new SimplePie;
        $feed->enable_cache(false);
        $feed->set_raw_data($xml);
        $feed->init();

        return $this->itemsFromFeed($feed);
    }

    /**
     * @return list<array{external_id: string, title: string, snippet: string}>
     */
    protected function itemsFromFeed(SimplePie $feed): array
    {
        $items = [];

        foreach ($feed->get_items() ?? [] as $item) {
            $title = trim((string) $item->get_title());
            $guid = trim((string) ($item->get_id() ?: $item->get_permalink() ?: $title));
            $raw = strip_tags((string) $item->get_description());
            $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', $raw) ?? $raw), 0, 400);

            if ($title === '' || $guid === '') {
                continue;
            }

            $items[] = [
                'external_id' => mb_substr($guid, 0, 512),
                'title' => mb_substr($title, 0, 512),
                'snippet' => $snippet,
            ];
        }

        return $items;
    }
}
