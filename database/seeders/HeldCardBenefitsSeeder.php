<?php

namespace Database\Seeders;

use App\Enums\CardProduct;
use App\Models\Card;
use App\Services\TravelWallet\BenefitRefresher;
use App\Services\TravelWallet\CardProductCatalog;
use Illuminate\Database\Seeder;

class HeldCardBenefitsSeeder extends Seeder
{
    public function __construct(private CardProductCatalog $catalog) {}

    public function run(): void
    {
        foreach (Card::query()->get() as $card) {
            $product = CardProduct::fromCardName($card->name);
            if ($product === null) {
                continue;
            }

            $this->catalog->apply($card, $product, refresh: false);
        }

        app(BenefitRefresher::class)->refreshAll();
    }
}
