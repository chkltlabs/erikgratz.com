<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Models\Card;
use App\Models\User;
use Database\Seeders\LocalCardsSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalCardsSeederTest extends TestCase
{
    #[Test]
    public function it_is_a_no_op_outside_local(): void
    {
        $this->seed(LocalCardsSeeder::class);

        $this->assertSame(0, Card::query()->count());
    }

    #[Test]
    public function it_upserts_the_held_wallet_cards_for_local_erik_and_amy(): void
    {
        $this->app['env'] = 'local';

        $this->seed(LocalCardsSeeder::class);

        $this->assertSame(15, Card::query()->count());
        $this->assertSame(
            User::erik()->id,
            Card::query()->where('name', 'Amex Plat (Erik)')->value('user_id'),
        );
        $this->assertSame(
            User::amy()->id,
            Card::query()->where('name', 'Sapphire Reserve (Amy)')->value('user_id'),
        );
        $this->assertSame(
            User::amy()->id,
            Card::query()->where('name', 'WPCU CC')->value('user_id'),
        );
        $this->assertEquals(895, Card::query()->where('name', 'Amex Plat (Erik)')->value('annual_fee'));

        $this->seed(LocalCardsSeeder::class);

        $this->assertSame(15, Card::query()->count());
    }
}
