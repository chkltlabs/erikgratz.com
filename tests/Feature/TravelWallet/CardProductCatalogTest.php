<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\CardProduct;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Services\TravelWallet\CardProductCatalog;
use Database\Seeders\TravelWalletCatalogSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardProductCatalogTest extends TestCase
{
    #[Test]
    #[DataProvider('cardNames')]
    public function from_card_name_matches_held_product_nicknames(string $name, string $product): void
    {
        $this->assertTrue(CardProduct::fromCardName($name)?->is(CardProduct::fromValue($product)));
    }

    #[Test]
    public function from_card_name_ignores_unknown_products(): void
    {
        $this->assertNull(CardProduct::fromCardName('Chase Ink Preferred (Erik)'));
    }

    #[Test]
    public function options_use_clear_catalogue_names(): void
    {
        $options = app(CardProductCatalog::class)->options();

        $this->assertSame('Amex Plat', $options[CardProduct::AmexPlat]);
        $this->assertSame('Amex Green', $options[CardProduct::AmexGreen]);
        $this->assertSame('Chase Aer Lingus', $options[CardProduct::ChaseAerlingus]);
        $this->assertSame('Chase Aeroplan', $options[CardProduct::ChaseAeroplan]);
        $this->assertArrayNotHasKey('plat', $options);
        $this->assertArrayNotHasKey('green', $options);
        $this->assertArrayNotHasKey('aerlingus', $options);
        $this->assertArrayNotHasKey('aeroplan', $options);
    }

    #[Test]
    public function apply_copies_the_unused_real_world_set_onto_a_new_card(): void
    {
        $this->seed(TravelWalletCatalogSeeder::class);

        $card = Card::factory()->create(['name' => 'New metal card']);

        $this->assertTrue(app(CardProductCatalog::class)->apply($card, CardProduct::Csr()));

        $benefits = CardBenefit::query()->where('card_id', $card->id)->get();

        $this->assertCount(13, $benefits);
        $this->assertTrue($benefits->every(fn (CardBenefit $benefit): bool => $benefit->is_used === false));
        $this->assertTrue($benefits->contains('benefit', '$300 travel credit'));
        $this->assertTrue($benefits->contains('benefit', '$500 The Edit credit'));
        $this->assertGreaterThan(0, $benefits->firstWhere('benefit', '$300 travel credit')->remaining());
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function cardNames(): array
    {
        return [
            ['Sapphire Reserve (Amy)', CardProduct::Csr],
            ['Sapphire Preferred (Erik)', CardProduct::Csp],
            ['C1 V X (Erik)', CardProduct::Vx],
            ['Venture X', CardProduct::Vx],
            ['Amex Plat (Erik)', CardProduct::AmexPlat],
            ['Amex Green (Amy)', CardProduct::AmexGreen],
            ['Chase BA (Erik)', CardProduct::Ba],
            ['British Airways', CardProduct::Ba],
            ['Aer Lingus (Erik)', CardProduct::ChaseAerlingus],
            ['Chase Aeroplan (Amy)', CardProduct::ChaseAeroplan],
        ];
    }
}
