<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\CardResource;
use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Filament\Resources\CardResource\RelationManagers\PaymentsRelationManager;
use App\Models\Card;
use App\Models\Payment;
use App\Models\Spend;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentsRelationManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function card_resource_registers_the_payments_relation_manager(): void
    {
        $this->assertContains(PaymentsRelationManager::class, CardResource::getRelations());
    }

    #[Test]
    public function infolist_shows_upcoming_and_recently_paid_payments_for_the_card(): void
    {
        $card = Card::factory()->create();
        $otherCard = Card::factory()->create();

        $upcoming = $this->paymentOnCard($card, [
            'name' => 'Upcoming gym',
            'amount' => 42.5,
            'is_paid' => false,
            'paid_on' => now()->addDays(5)->toDateString(),
        ]);
        $recent = $this->paymentOnCard($card, [
            'name' => 'Recent hotel',
            'amount' => 180.0,
            'is_paid' => true,
            'paid_on' => now()->subDays(3)->toDateString(),
        ]);
        $old = $this->paymentOnCard($card, [
            'name' => 'Old flight',
            'amount' => 900.0,
            'is_paid' => true,
            'paid_on' => now()->subDays(Payment::RECENTLY_PAID_WITHIN_DAYS + 1)->toDateString(),
        ]);
        $other = $this->paymentOnCard($otherCard, [
            'name' => 'Other card taxi',
            'amount' => 25.0,
            'is_paid' => false,
            'paid_on' => now()->addDay()->toDateString(),
        ]);

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->assertSuccessful()
            ->assertSee('Upcoming')
            ->assertSee('Recently paid')
            ->assertSee($upcoming->spend->name)
            ->assertSee('$42.50')
            ->assertSee($recent->spend->name)
            ->assertSee('$180.00')
            ->assertDontSee($old->spend->name)
            ->assertDontSee($other->spend->name);
    }

    #[Test]
    public function infolist_shows_empty_placeholders_when_the_card_has_no_visible_payments(): void
    {
        $card = Card::factory()->create();

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->assertSuccessful()
            ->assertSee('No upcoming payments')
            ->assertSee('No recently paid payments');
    }

    /**
     * @param  array{name: string, amount: float, is_paid: bool, paid_on: string}  $attributes
     */
    private function paymentOnCard(Card $card, array $attributes): Payment
    {
        $spend = Spend::factory()->bare()->noPayments()->create([
            'name' => $attributes['name'],
        ]);

        return Payment::factory()->create([
            'card_id' => $card->id,
            'spend_id' => $spend->id,
            'spend_type' => getMorphAliasForClass(Spend::class),
            'amount' => $attributes['amount'],
            'is_paid' => $attributes['is_paid'],
            'paid_on' => $attributes['paid_on'],
        ]);
    }
}
