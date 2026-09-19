<?php

declare(strict_types=1);

namespace Tests\Unit\TravelWallet;

use App\Enums\RecommendationAction;
use App\Services\Ai\Parsing\FrontierChatParser;
use App\Services\Ai\Parsing\FrontierChatParserResolver;
use App\Services\Ai\Parsing\GeminiExportParser;
use App\Services\Ai\Parsing\GoogleTakeoutHtmlParser;
use App\Services\Ai\Parsing\PlaintextFrontierChatParser;
use App\Services\TravelWallet\PromoSources\DoctorOfCreditRssSource;
use App\Services\TravelWallet\PromoSources\PromoSource;
use App\Services\TravelWallet\Recommendation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecommendationAndContractsTest extends TestCase
{
    #[Test]
    public function recommendation_serializes_action_and_ids(): void
    {
        $recommendation = new Recommendation(
            action: RecommendationAction::Transfer(),
            score: 12.345,
            headline: 'Move UR to United',
            reasons: ['30% bonus'],
            cardId: 4,
            benefitId: 8,
            membershipId: 16,
            transferRouteId: 32,
            meta: ['bonus_percent' => 30],
        );

        $this->assertSame([
            'action' => RecommendationAction::Transfer,
            'score' => 12.35,
            'headline' => 'Move UR to United',
            'reasons' => ['30% bonus'],
            'card_id' => 4,
            'benefit_id' => 8,
            'membership_id' => 16,
            'transfer_route_id' => 32,
            'meta' => ['bonus_percent' => 30],
        ], $recommendation->toArray());
    }

    #[Test]
    public function doctor_of_credit_is_the_promo_source(): void
    {
        $source = app(DoctorOfCreditRssSource::class);

        $this->assertInstanceOf(PromoSource::class, $source);
        $this->assertSame('doctor_of_credit', $source->key());
    }

    #[Test]
    public function frontier_chat_parsers_implement_the_parser_contract(): void
    {
        $parsers = [
            new GoogleTakeoutHtmlParser,
            new GeminiExportParser,
            new PlaintextFrontierChatParser,
        ];

        foreach ($parsers as $parser) {
            $this->assertInstanceOf(FrontierChatParser::class, $parser);
        }

        $resolver = app(FrontierChatParserResolver::class);
        $parsed = $resolver->parse("User: Hello\nAssistant: Hi");

        $this->assertNotSame('', $parsed->title);
        $this->assertNotEmpty($parsed->messages);
    }
}
