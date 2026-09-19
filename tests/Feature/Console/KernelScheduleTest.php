<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Kernel;
use App\Jobs\DailyUpkeep;
use App\Jobs\HourlyUpkeep;
use App\Jobs\IngestTravelPromos;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class KernelScheduleTest extends TestCase
{
    #[Test]
    public function schedule_registers_travel_wallet_and_upkeep_jobs(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $kernel = $this->app->make(Kernel::class);

        $method = new ReflectionMethod(Kernel::class, 'schedule');
        $method->invoke($kernel, $schedule);

        $descriptions = collect($schedule->events())
            ->map(fn (Event $event): string => $event->description ?? $event->command ?? '')
            ->implode("\n");

        $this->assertStringContainsString(HourlyUpkeep::class, $descriptions);
        $this->assertStringContainsString(DailyUpkeep::class, $descriptions);
        $this->assertStringContainsString(IngestTravelPromos::class, $descriptions);
        $this->assertStringContainsString('fx:refresh', $descriptions);
        $this->assertStringContainsString('cache:prune-stale-tags', $descriptions);
    }
}
