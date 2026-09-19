<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Ai\Agents\BookingExplainerAgent;
use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Enums\LoyaltyKind;
use App\Enums\PointsProgram;
use App\Models\BookingIntent;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Services\TravelWallet\BookingCombo;
use App\Services\TravelWallet\BookingExplainer;
use App\Services\TravelWallet\BookingRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingExplainerTest extends TestCase
{
    #[Test]
    public function explain_asks_the_agent_with_wallet_and_combo_snapshot(): void
    {
        BookingExplainerAgent::fake(function (string $prompt): string {
            $this->assertStringContainsString('Sapphire', $prompt);
            $this->assertStringContainsString('Aeroplan', $prompt);
            $this->assertStringContainsString('United', $prompt);

            return 'Charge Sapphire and keep the Aeroplan companion perk.';
        });

        $card = Card::factory()->create([
            'name' => 'Sapphire',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
        ]);
        BookingPerk::factory()->create([
            'card_id' => $card->id,
            'name' => 'Priority boarding',
        ]);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Air Canada Aeroplan',
            'code' => 'aeroplan',
        ]);
        LoyaltyMembership::factory()->create([
            'loyalty_program_id' => $program->id,
            'tier' => '25K',
            'points_balance' => 40000,
        ]);

        $request = new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'United',
            destination: 'YYZ',
            cashPrice: 400,
            cabin: BookingCabin::Economy(),
        );
        $ranking = [
            new BookingCombo(
                headline: 'Sapphire direct',
                score: 12.5,
                channel: BookingChannel::Direct(),
                cardId: $card->id,
                cardName: 'Sapphire',
                reasons: ['Base earn'],
            ),
        ];

        $text = app(BookingExplainer::class)->explain($request, $ranking);

        $this->assertSame('Charge Sapphire and keep the Aeroplan companion perk.', $text);
    }

    #[Test]
    public function persist_stores_the_ranked_intent(): void
    {
        $request = new BookingRequest(
            category: BookingCategory::Hotel(),
            vendor: 'Hyatt',
            destination: 'CHI',
            cashPrice: 220,
            cabin: BookingCabin::Economy(),
            awardQuotes: [['points_program' => 'chaseUltimateRewards', 'points' => 18000]],
        );
        $ranking = [
            new BookingCombo(
                headline: 'Pay cash at Hyatt',
                score: 8,
                channel: BookingChannel::Direct(),
                reasons: ['No unused hotel credit'],
            ),
        ];

        $intent = app(BookingExplainer::class)->persist($request, $ranking, 'Skip the portal.');

        $this->assertInstanceOf(BookingIntent::class, $intent);
        $this->assertTrue($intent->category->is(BookingCategory::Hotel));
        $this->assertSame('Hyatt', $intent->vendor);
        $this->assertSame('Skip the portal.', $intent->explanation);
        $this->assertSame('Pay cash at Hyatt', $intent->ranking[0]['headline']);
    }
}
