<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivityResource\Widgets\ActivityTimelineChart;
use App\Models\Activity;
use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;
use TypeError;

class ActivityTimelineChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function get_options_includes_active_activities_and_excludes_archived(): void
    {
        Activity::query()->delete();

        $active = Activity::factory()->create([
            'name' => 'Active Trip',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        Activity::factory()->create([
            'name' => 'Archived Trip',
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->subDays(Activity::ARCHIVE_DAY_GRACE + 5)->toDateString(),
        ]);

        $options = $this->invokeGetOptions();

        $this->assertSame('rangeBar', $options['chart']['type']);
        $this->assertSame(['Paid', 'Unpaid', 'Cards'], array_column($options['series'], 'name'));

        $seriesNames = collect($options['series'])
            ->flatMap(fn (array $series) => collect($series['data'])->pluck('name'))
            ->filter()
            ->values()
            ->all();

        $this->assertContains('Active Trip', $seriesNames);
        $this->assertNotContains('Archived Trip', $seriesNames);
        $this->assertNotNull($active->id);
        $this->assertApexRangeBarSeriesAreJsonArrays($options);
    }

    #[Test]
    public function series_data_json_encodes_as_arrays_when_archived_rows_leave_collection_holes(): void
    {
        Activity::query()->delete();
        Card::query()->delete();

        Activity::factory()->create([
            'name' => 'Old Archived Trip',
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->subDays(Activity::ARCHIVE_DAY_GRACE + 20)->toDateString(),
        ]);
        Activity::factory()->create([
            'name' => 'Also Archived',
            'start_date' => now()->subMonths(4)->toDateString(),
            'end_date' => now()->subDays(Activity::ARCHIVE_DAY_GRACE + 5)->toDateString(),
        ]);
        Activity::factory()->create([
            'name' => 'Current Trip',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
        ]);

        $component = Livewire::test(ActivityTimelineChart::class)->assertSuccessful();
        $options = $component->get('options');

        $this->assertIsArray($options);
        $this->assertApexRangeBarSeriesAreJsonArrays($options);

        $seriesNames = collect($options['series'])
            ->flatMap(fn (array $series) => collect($series['data'])->pluck('name'))
            ->filter()
            ->values()
            ->all();

        $this->assertContains('Current Trip', $seriesNames);
        $this->assertNotContains('Old Archived Trip', $seriesNames);
        $this->assertNotContains('Also Archived', $seriesNames);
    }

    #[Test]
    public function get_options_omits_cards_with_expired_or_satisfied_subs(): void
    {
        Activity::query()->delete();
        Card::query()->delete();

        Card::factory()->create([
            'name' => 'Open SUB',
            'date_opened' => now()->toDateString(),
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 4000,
            'balance' => 0,
            'pending' => 0,
            'color' => '#126bc5',
        ]);
        Card::factory()->create([
            'name' => 'Expired SUB',
            'date_opened' => now()->subYear()->toDateString(),
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 4000,
            'balance' => 0,
            'pending' => 0,
        ]);
        Card::factory()->create([
            'name' => 'Satisfied SUB',
            'date_opened' => now()->toDateString(),
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 100,
            'balance' => 150,
            'pending' => 0,
        ]);

        $options = $this->invokeGetOptions();
        $paidNames = collect($options['series'][0]['data'])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
        $cardPoints = collect($options['series'][2]['data']);

        $this->assertNotContains('Open SUB', $paidNames);
        $this->assertNotContains('Expired SUB', $paidNames);
        $this->assertNotContains('Satisfied SUB', $paidNames);
        $this->assertSame(['Open SUB'], $cardPoints->pluck('name')->all());
        $this->assertSame(['cards'], $cardPoints->pluck('x')->all());
        $this->assertSame(
            [ActivityTimelineChart::darkenHex('#126bc5')],
            $cardPoints->pluck('fillColor')->all(),
        );
        $this->assertTrue($cardPoints->first()['showLabel']);
        $this->assertTrue($options['yaxis']['labels']['show']);
        $this->assertApexRangeBarSeriesAreJsonArrays($options);
    }

    #[Test]
    public function get_options_splits_card_bars_at_sub_completion_with_darker_remaining(): void
    {
        Activity::query()->delete();
        Card::query()->delete();

        $card = Card::factory()->create([
            'name' => 'Halfway SUB',
            'date_opened' => now()->toDateString(),
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 4000,
            'balance' => 2000,
            'pending' => 0,
            'color' => '#126bc5',
        ]);

        $options = $this->invokeGetOptions();
        $cardPoints = collect($options['series'][2]['data'])->values();

        $this->assertCount(2, $cardPoints);
        $this->assertSame(['Halfway SUB', 'Halfway SUB'], $cardPoints->pluck('name')->all());
        $this->assertSame(['cards', 'cards'], $cardPoints->pluck('x')->all());
        $this->assertTrue($cardPoints[0]['showLabel']);
        $this->assertFalse($cardPoints[1]['showLabel']);
        $this->assertSame('#126bc5', $cardPoints[0]['fillColor']);
        $this->assertSame(ActivityTimelineChart::darkenHex('#126bc5'), $cardPoints[1]['fillColor']);
        $this->assertSame($cardPoints[0]['y'][1], $cardPoints[1]['y'][0]);

        $opened = Carbon::parse($card->date_opened)->valueOf();
        $deadline = Carbon::parse($card->date_opened)->modify('+3 months')->valueOf();
        $mid = $opened + (($deadline - $opened) * 0.5);
        $this->assertEqualsWithDelta($mid, $cardPoints[0]['y'][1], 1);
        $this->assertApexRangeBarSeriesAreJsonArrays($options);
    }

    #[Test]
    public function format_for_data_array_maps_activity_fields(): void
    {
        $activity = Activity::factory()->create([
            'name' => 'Mapped Activity',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
        ]);

        $method = new ReflectionMethod(ActivityTimelineChart::class, 'formatForDataArray');
        $formatted = $method->invoke(null, new Collection([7 => $activity]));

        $this->assertTrue(array_is_list($formatted));
        $this->assertCount(1, $formatted);
        $this->assertNull($formatted[0]['x']);
        $this->assertSame('Mapped Activity', $formatted[0]['name']);
        $this->assertSame(Activity::class, $formatted[0]['class']);
        $this->assertIsNumeric($formatted[0]['y'][0]);
        $this->assertIsNumeric($formatted[0]['y'][1]);
        $this->assertSame('#32cd32', $formatted[0]['fillColor']);
        $this->assertSame(ActivityTimelineChart::darkenHex('#32cd32'), $formatted[0]['unpaidFillColor']);
        $this->assertSame(10, $formatted[0]['days']);
        $this->assertStringContainsString((string) $activity->id, $formatted[0]['link']);
    }

    #[Test]
    public function format_for_data_array_uses_activity_color_when_set(): void
    {
        $activity = Activity::factory()->create([
            'name' => 'Colored Activity',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
            'color' => '#ff9900',
        ]);

        $method = new ReflectionMethod(ActivityTimelineChart::class, 'formatForDataArray');
        $formatted = $method->invoke(null, new Collection([$activity]));

        $this->assertSame('#ff9900', $formatted[0]['fillColor']);
        $this->assertSame(ActivityTimelineChart::darkenHex('#ff9900'), $formatted[0]['unpaidFillColor']);
    }

    #[Test]
    public function format_cards_for_data_array_maps_card_fields(): void
    {
        $card = Card::factory()->create([
            'name' => 'Travel Card',
            'date_opened' => '2026-02-01',
            'points_bonus_period' => '+1 month',
            'points_bonus_spend' => 3000,
            'balance' => 0,
            'pending' => 0,
            'color' => '#ff9900',
        ]);

        $method = new ReflectionMethod(ActivityTimelineChart::class, 'formatCardsForDataArray');
        $formatted = $method->invoke(null, new Collection([12 => $card]));

        $this->assertTrue(array_is_list($formatted));
        $this->assertCount(1, $formatted);
        $this->assertSame('cards', $formatted[0]['x']);
        $this->assertSame('Travel Card', $formatted[0]['name']);
        $this->assertSame('#ff9900', $formatted[0]['fillColor']);
        $this->assertSame(ActivityTimelineChart::darkenHex('#ff9900'), $formatted[0]['unpaidFillColor']);
        $this->assertSame(0.0, $formatted[0]['paid']);
        $this->assertSame(3000.0, $formatted[0]['unpaid']);
        $this->assertSame(Card::class, $formatted[0]['class']);
        $this->assertEquals(3000, $formatted[0]['amount']);
        $this->assertIsNumeric($formatted[0]['y'][0]);
        $this->assertIsNumeric($formatted[0]['y'][1]);
    }

    #[Test]
    public function extra_js_options_and_schema_stubs_are_callable(): void
    {
        $widget = app(ActivityTimelineChart::class);

        $extraJs = (new ReflectionMethod($widget, 'extraJsOptions'))->invoke($widget);
        $this->assertInstanceOf(RawJs::class, $extraJs);
        $this->assertStringContainsString('data.showLabel', (string) $extraJs);
        $this->assertStringContainsString('data.days', (string) $extraJs);

        $formSchema = (new ReflectionMethod($widget, 'getFormSchema'))->invoke($widget);
        $this->assertSame([], $formSchema);

        try {
            $widget->getOldSchemaState('unused');
            $this->fail('Expected TypeError from empty getOldSchemaState stub');
        } catch (TypeError) {
            // Stub has no return; invoking still covers the method body.
        }

        try {
            $widget->getSchema('unused');
            $this->fail('Expected TypeError from empty getSchema stub');
        } catch (TypeError) {
            // Stub has no return.
        }

        try {
            $widget->getDefaultTestingSchemaName();
            $this->fail('Expected TypeError from empty getDefaultTestingSchemaName stub');
        } catch (TypeError) {
            // Stub has no return.
        }

        $widget->currentlyValidatingSchema(null);
        $widget->currentlyValidatingSchema($this->createMock(Schema::class));
    }

    private function invokeGetOptions(): array
    {
        $widget = app(ActivityTimelineChart::class);
        $method = new ReflectionMethod($widget, 'getOptions');

        return $method->invoke($widget);
    }

    /**
     * ApexCharts calls `series[i].data.filter`. Livewire JSON-encodes options; PHP arrays
     * with holes become objects, which throw TypeError in the browser.
     *
     * @param  array<string, mixed>  $options
     */
    private function assertApexRangeBarSeriesAreJsonArrays(array $options): void
    {
        $this->assertArrayHasKey('series', $options);

        $decoded = json_decode(json_encode($options, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        foreach ($decoded['series'] as $series) {
            $this->assertIsArray($series['data'], $series['name'].' data must decode as a JSON array');
            $this->assertTrue(
                array_is_list($series['data']),
                $series['name'].' data must be a JSON array, not an object with collection holes',
            );

            foreach ($series['data'] as $index => $point) {
                $this->assertIsArray($point, $series['name']."[{$index}] must be a point");
                $this->assertNotSame([], $point, $series['name']."[{$index}] must not be an empty placeholder");
                $this->assertArrayHasKey('x', $point);
                $this->assertArrayHasKey('y', $point);
                $this->assertTrue(array_is_list($point['y']), $series['name']."[{$index}].y must be a list");
                $this->assertCount(2, $point['y']);
            }
        }
    }
}
