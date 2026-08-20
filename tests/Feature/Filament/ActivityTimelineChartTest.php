<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivityResource\Widgets\ActivityTimelineChart;
use App\Models\Activity;
use App\Models\Card;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Collection;
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
        $this->assertSame(['Paid', 'Unpaid'], array_column($options['series'], 'name'));

        $paidNames = collect($options['series'][0]['data'])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $this->assertContains('Active Trip', $paidNames);
        $this->assertNotContains('Archived Trip', $paidNames);
        $this->assertNotNull($active->id);
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
        $formatted = $method->invoke(null, new Collection([$activity]));

        $this->assertCount(1, $formatted);
        $this->assertNull($formatted[0]['x']);
        $this->assertSame('Mapped Activity', $formatted[0]['name']);
        $this->assertSame(Activity::class, $formatted[0]['class']);
        $this->assertIsNumeric($formatted[0]['y'][0]);
        $this->assertIsNumeric($formatted[0]['y'][1]);
        $this->assertStringContainsString((string) $activity->id, $formatted[0]['link']);
    }

    #[Test]
    public function format_cards_for_data_array_maps_card_fields(): void
    {
        $card = Card::factory()->create([
            'name' => 'Travel Card',
            'date_opened' => '2026-02-01',
            'points_bonus_period' => '+1 month',
            'points_bonus_spend' => 3000,
        ]);

        $method = new ReflectionMethod(ActivityTimelineChart::class, 'formatCardsForDataArray');
        $formatted = $method->invoke(null, new Collection([$card]));

        $this->assertCount(1, $formatted);
        $this->assertSame('card', $formatted[0]['x']);
        $this->assertSame('Travel Card', $formatted[0]['name']);
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
}
