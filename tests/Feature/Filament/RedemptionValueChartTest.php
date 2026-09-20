<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Widgets\BenefitUsageChart;
use App\Filament\Widgets\RedemptionValueChart;
use App\Models\PointRedemption;
use App\Models\StateDump;
use App\Models\User;
use App\Services\Dashboard\DumpChartData;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class RedemptionValueChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-15');
        Cache::forget(DumpChartData::REDEMPTIONS_CACHE);
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget(DumpChartData::REDEMPTIONS_CACHE);

        parent::tearDown();
    }

    #[Test]
    public function it_is_lazy_and_registered_below_benefit_usage(): void
    {
        $this->assertTrue(RedemptionValueChart::isLazy());

        $widgets = filament()->getPanel('admin')->getWidgets();

        $this->assertContains(RedemptionValueChart::class, $widgets);
        $this->assertGreaterThan(
            array_search(BenefitUsageChart::class, $widgets, true),
            array_search(RedemptionValueChart::class, $widgets, true),
        );
    }

    #[Test]
    public function get_options_fills_the_area_between_cash_value_and_cash_spent(): void
    {
        StateDump::factory()->create([
            'data' => [
                PointRedemption::class => [[
                    'id' => 1,
                    'points_spent' => 40000,
                    'money_spent' => 50,
                    'cash_value' => 600,
                ]],
            ],
        ]);

        Livewire::test(RedemptionValueChart::class)->assertSuccessful();

        $widget = app(RedemptionValueChart::class);
        $options = (new ReflectionMethod(RedemptionValueChart::class, 'getOptions'))->invoke($widget);
        $extraJs = (new ReflectionMethod(RedemptionValueChart::class, 'extraJsOptions'))->invoke($widget);

        $this->assertSame('rangeArea', $options['chart']['type']);
        $this->assertSame(['Money saved', 'Cash value', 'Cash spent'], array_column($options['series'], 'name'));
        $this->assertSame('rangeArea', $options['series'][0]['type']);
        $this->assertSame('line', $options['series'][1]['type']);
        $this->assertSame('line', $options['series'][2]['type']);
        $this->assertSame('40,000 pts / $550.00 saved = 1.38¢/pt', $options['series'][1]['data'][0]['label']);
        $this->assertInstanceOf(RawJs::class, $extraJs);
        $this->assertStringContainsString('initialSeries[1].data', (string) $extraJs);
        $this->assertStringNotContainsString('"', (string) $extraJs);
    }
}
