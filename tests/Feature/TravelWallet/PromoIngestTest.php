<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Ai\Agents\PromoExtractionAgent;
use App\Enums\PointsProgram;
use App\Enums\PromoStatus;
use App\Jobs\IngestTravelPromos;
use App\Models\LoyaltyProgram;
use App\Models\PromoSourceItem;
use App\Models\TransferBonus;
use App\Models\TransferRoute;
use App\Services\TravelWallet\PromoIngestor;
use App\Services\TravelWallet\PromoSources\DoctorOfCreditRssSource;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Conversational;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromoIngestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        PromoExtractionAgent::fake(function (string $prompt): string {
            if (str_contains(strtolower($prompt), 'united')) {
                return json_encode([
                    'items' => [[
                        'type' => 'transfer_bonus',
                        'from_program' => PointsProgram::ChaseUltimateRewards,
                        'to_program_code' => 'united',
                        'bonus_percent' => 30,
                        'starts_at' => '2026-09-01',
                        'ends_at' => '2026-09-30',
                        'summary' => '30% UR to United',
                    ]],
                ]);
            }

            return '{"items":[]}';
        });
    }

    #[Test]
    public function extraction_agent_is_not_conversational(): void
    {
        $this->assertNotInstanceOf(Conversational::class, app(PromoExtractionAgent::class));
    }

    #[Test]
    public function rss_parser_reads_item_facts_not_article_bodies(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<rss version="2.0">
  <channel>
    <item>
      <title>30% Chase transfer bonus to United</title>
      <guid>doc-united-30</guid>
      <description><![CDATA[<p>Long article copy that must not be stored.</p>]]></description>
    </item>
  </channel>
</rss>
XML;

        $items = app(DoctorOfCreditRssSource::class)->parse($xml);

        $this->assertCount(1, $items);
        $this->assertSame('doc-united-30', $items[0]['external_id']);
        $this->assertSame('30% Chase transfer bonus to United', $items[0]['title']);
        $this->assertStringNotContainsString('<p>', $items[0]['snippet']);
    }

    #[Test]
    public function ingest_job_creates_pending_review_items_from_rss(): void
    {
        Http::fake([
            '*' => Http::response(<<<'XML'
<?xml version="1.0"?>
<rss version="2.0"><channel>
<item>
  <title>30% Chase transfer bonus to United</title>
  <guid>doc-united-30</guid>
  <description>Short blurb</description>
</item>
</channel></rss>
XML, 200),
        ]);

        IngestTravelPromos::dispatchSync();

        $item = PromoSourceItem::query()->first();
        $this->assertNotNull($item);
        $this->assertTrue($item->status->is(PromoStatus::PendingReview));
        $this->assertSame('transfer_bonus', $item->facts['type']);
        $this->assertSame(30, $item->facts['bonus_percent']);
    }

    #[Test]
    public function approve_creates_an_active_transfer_bonus(): void
    {
        $program = LoyaltyProgram::factory()->create([
            'code' => 'united',
            'name' => 'United MileagePlus',
        ]);
        TransferRoute::factory()->create([
            'from_program' => PointsProgram::ChaseUltimateRewards,
            'loyalty_program_id' => $program->id,
        ]);

        $item = PromoSourceItem::factory()->create([
            'facts' => [
                'type' => 'transfer_bonus',
                'from_program' => PointsProgram::ChaseUltimateRewards,
                'to_program_code' => 'united',
                'bonus_percent' => 40,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
            ],
        ]);

        app(PromoIngestor::class)->approve($item);

        $this->assertTrue($item->fresh()->status->is(PromoStatus::Active));
        $this->assertTrue(TransferBonus::query()->where('bonus_percent', 40)->where('status', PromoStatus::Active)->exists());
    }
}
