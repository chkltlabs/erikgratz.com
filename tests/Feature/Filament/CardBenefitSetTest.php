<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\CardProduct;
use App\Filament\Resources\CardResource\Pages\CreateCard;
use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\User;
use Database\Seeders\TravelWalletCatalogSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardBenefitSetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->seed(TravelWalletCatalogSeeder::class);
    }

    #[Test]
    public function creating_a_card_with_a_benefit_set_copies_unused_official_credits(): void
    {
        $card = Card::factory()->make([
            'name' => 'New metal card',
        ]);

        Livewire::test(CreateCard::class)
            ->fillForm([
                ...$card->toArray(),
                'benefit_set' => CardProduct::Csr,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Card::query()->where('name', 'New metal card')->first();

        $this->assertNotNull($created);
        $this->assertCount(13, CardBenefit::query()->where('card_id', $created->id)->get());
        $this->assertTrue(
            CardBenefit::query()
                ->where('card_id', $created->id)
                ->where('benefit', '$300 travel credit')
                ->where('is_used', false)
                ->exists()
        );
    }

    #[Test]
    public function creating_a_card_without_a_set_leaves_benefits_empty_when_the_name_does_not_match(): void
    {
        $card = Card::factory()->make([
            'name' => 'Random store card',
        ]);

        Livewire::test(CreateCard::class)
            ->fillForm($card->toArray())
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Card::query()->where('name', 'Random store card')->first();

        $this->assertSame(0, CardBenefit::query()->where('card_id', $created->id)->count());
    }

    #[Test]
    public function edit_card_can_apply_a_benefit_set_to_an_opened_card(): void
    {
        $card = Card::factory()->create(['name' => 'Opened without credits']);

        Livewire::test(EditCard::class, ['record' => $card->getKey()])
            ->callAction('applyBenefitSet', data: [
                'benefit_set' => CardProduct::AmexPlat,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Benefit set applied');

        $this->assertCount(14, CardBenefit::query()->where('card_id', $card->id)->get());
        $this->assertTrue(
            CardBenefit::query()
                ->where('card_id', $card->id)
                ->where('benefit', '$300 FHR / Hotel Collection')
                ->where('is_used', false)
                ->exists()
        );
    }
}
