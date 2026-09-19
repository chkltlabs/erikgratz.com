<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Ai\Agents\BookingExplainerAgent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingExplainerAgentTest extends TestCase
{
    #[Test]
    public function agent_is_not_conversational_and_reads_travel_wallet_config(): void
    {
        config([
            'travel-wallet.agents.provider' => 'gemini',
            'travel-wallet.agents.model' => 'gemini-2.5-flash',
            'travel-wallet.agents.timeout' => 45,
        ]);

        $agent = app(BookingExplainerAgent::class);

        $this->assertNotInstanceOf(Conversational::class, $agent);
        $this->assertSame(Lab::Gemini, $agent->provider());
        $this->assertSame('gemini-2.5-flash', $agent->model());
        $this->assertSame(45, $agent->timeout());
        $this->assertStringContainsString('Do not add options', (string) $agent->instructions());
    }

    #[Test]
    public function empty_model_config_returns_null(): void
    {
        config(['travel-wallet.agents.model' => null]);

        $this->assertNull(app(BookingExplainerAgent::class)->model());
    }
}
