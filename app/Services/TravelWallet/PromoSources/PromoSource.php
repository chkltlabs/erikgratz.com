<?php

declare(strict_types=1);

namespace App\Services\TravelWallet\PromoSources;

interface PromoSource
{
    public function key(): string;

    /**
     * @return list<array{external_id: string, title: string, snippet: string}>
     */
    public function fetch(): array;
}
