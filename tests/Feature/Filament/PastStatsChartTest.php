<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Widgets\PastStatsChart;
use App\Models\Account;
use App\Models\Card;
use App\Models\StateDump;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;
use TypeError;

class PastStatsChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-15');
        Cache::forget('stateDumps');
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget('stateDumps');

        parent::tearDown();
    }

    #[Test]
    public function it_loads_with_the_page_and_plots_dump_totals(): void
    {
        $this->assertSame('on-load', PastStatsChart::getDefaultProperties()['lazy']);
        $this->assertContains(PastStatsChart::class, filament()->getPanel('admin')->getWidgets());

        $card = Card::factory()->create();
        $account = Account::factory()->create();
        $dump = StateDump::factory()->create([
            'data' => [
                Card::class => [[
                    'id' => $card->id,
                    'balance' => 200,
                    'pending' => 40,
                    'points_balance' => 1500,
                ]],
                Account::class => [[
                    'id' => $account->id,
                    'balance' => 800,
                    'currency' => 'USD',
                ]],
                'exchange_rates' => [
                    'multipliers' => ['USD' => 1],
                ],
            ],
        ]);

        Livewire::test(PastStatsChart::class)->assertSuccessful();

        $options = (new ReflectionMethod(PastStatsChart::class, 'getOptions'))
            ->invoke(app(PastStatsChart::class));

        $this->assertSame('line', $options['chart']['type']);
        $this->assertSame(
            ['Net Worth', 'CC Debt', 'Cash Position', 'Points Balance'],
            array_column($options['series'], 'name'),
        );
        $this->assertSame(
            [560.0, 240.0, 800.0, 1500.0],
            array_map(
                fn (array $series): float => (float) $series['data'][0]['y'],
                $options['series'],
            ),
        );
        $this->assertSame(
            $dump->created_at->getTimestamp(),
            $options['series'][0]['data'][0]['x']->getTimestamp(),
        );
    }

    #[Test]
    public function flip_to_xy_rounds_values_and_keeps_a_list(): void
    {
        $points = PastStatsChart::flipToXY([
            '2026-05-01 00:00:00' => 10.6,
            '2026-05-02 00:00:00' => 20.2,
        ]);

        $this->assertSame([11.0, 20.0], array_column($points, 'y'));
        $this->assertSame('2026-05-01', $points[0]['x']->toDateString());
        $this->assertTrue(array_is_list($points));
    }

    #[Test]
    public function schema_stubs_are_callable(): void
    {
        $widget = app(PastStatsChart::class);
        $thrown = 0;

        foreach ([
            fn () => $widget->getOldSchemaState('unused'),
            fn () => $widget->getSchema('unused'),
            fn () => $widget->getDefaultTestingSchemaName(),
        ] as $call) {
            try {
                $call();
            } catch (TypeError) {
                $thrown++;
            }
        }

        $this->assertSame(3, $thrown);

        $this->assertNull($widget->currentlyValidatingSchema(null));
        $this->assertNull($widget->currentlyValidatingSchema($this->createMock(Schema::class)));
    }
}
