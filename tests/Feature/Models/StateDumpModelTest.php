<?php

namespace Tests\Feature\Models;

use App\Models\StateDump;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StateDumpModelTest extends TestCase
{
    public function test_dumping()
    {
        $c = 1;
        foreach (StateDump::$dumpables as $dumpable) {
            if ($dumpable::count() < $c) {
                $dumpable::factory($c)->create();
            }
        }

        $new = StateDump::dump();

        foreach (StateDump::$dumpables as $dumpable) {
            self::assertArrayHasKey($dumpable, $new->data);
        }
    }

    public function test_dumping_maximums()
    {
        // 5 is enough to test the mechanism without taking forever
        $c = 5;
        foreach (StateDump::$dumpables as $dumpable) {
            if ($dumpable::count() < $c) {
                $dumpable::factory($c)->create();
            }
        }

        $new = StateDump::dump();

        foreach (StateDump::$dumpables as $dumpable) {
            self::assertArrayHasKey($dumpable, $new->data);
        }
    }

    public function test_flag_sets()
    {
        // creates set it
        Cache::clear();
        self::assertFalse(Cache::has(StateDump::SHOULD_DUMP));
        foreach (StateDump::$dumpables as $dumpable) {
            $dumpable::factory()->create();
        }
        self::assertTrue(Cache::has(StateDump::SHOULD_DUMP));

        // updates too
        Cache::clear();
        self::assertFalse(Cache::has(StateDump::SHOULD_DUMP));
        foreach (StateDump::$dumpables as $dumpable) {
            $dumpable::first()->update($dumpable::factory()->make()->toArray());
        }
        self::assertTrue(Cache::has(StateDump::SHOULD_DUMP));

        Cache::clear();
    }
}
