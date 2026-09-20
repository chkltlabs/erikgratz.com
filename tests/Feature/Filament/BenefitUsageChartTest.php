<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Widgets\BenefitUsageChart;
use App\Filament\Widgets\PastStatsChart;
use App\Models\BenefitUsage;
use App\Models\Card;
use App\Models\StateDump;
use App\Models\User;
use App\Services\Dashboard\DumpChartData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class BenefitUsageChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-15');
        Cache::forget(DumpChartData::BENEFIT_USAGE_CACHE);
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget(DumpChartData::BENEFIT_USAGE_CACHE);

        parent::tearDown();
    }

    #[Test]
    public function it_is_lazy_and_registered_below_past_stats(): void
    {
        $this->assertTrue(BenefitUsageChart::isLazy());

        $widgets = filament()->getPanel('admin')->getWidgets();

        $this->assertContains(BenefitUsageChart::class, $widgets);
        $this->assertGreaterThan(
            array_search(PastStatsChart::class, $widgets, true),
            array_search(BenefitUsageChart::class, $widgets, true),
        );
    }

    #[Test]
    public function get_options_includes_total_and_per_card_series(): void
    {
        $card = Card::factory()->create(['name' => 'Venture X', 'color' => '#ef4444']);
        StateDump::factory()->create([
            'data' => [
                Card::class => [[
                    'id' => $card->id,
                    'annual_fee' => 395,
                    'date_opened' => '2025-05-15',
                ]],
                BenefitUsage::class => [[
                    'id' => 1,
                    'card_id' => $card->id,
                    'captured' => 500,
                ]],
            ],
        ]);

        Livewire::test(BenefitUsageChart::class)->assertSuccessful();

        $options = (new ReflectionMethod(BenefitUsageChart::class, 'getOptions'))
            ->invoke(app(BenefitUsageChart::class));

        $this->assertSame('line', $options['chart']['type']);
        $this->assertSame(['Total', 'Venture X'], array_column($options['series'], 'name'));
        $this->assertSame('#111827', $options['colors'][0]);
        $this->assertSame('#ef4444', $options['colors'][1]);
    }
}
