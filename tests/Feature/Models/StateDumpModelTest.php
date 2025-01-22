<?php

namespace Tests\Feature\Models;

use App\Models\StateDump;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StateDumpModelTest extends TestCase
{
    public function testDumping()
    {
        $c = 3;
        foreach(StateDump::$dumpables as $dumpable) {
            if ($dumpable::count() < $c){
                $dumpable::factory($c)->create();
            }
        }

        $new = StateDump::dump();

        foreach(StateDump::$dumpables as $dumpable) {
            self::assertArrayHasKey($dumpable, $new->data);
        }
    }
    public function testDumpingMaximums()
    {
        //30
        $c = 30;
        foreach(StateDump::$dumpables as $dumpable) {
            if ($dumpable::count() < $c){
                $dumpable::factory($c)->create();
            }
        }

        $new = StateDump::dump();

        foreach(StateDump::$dumpables as $dumpable) {
            self::assertArrayHasKey($dumpable, $new->data);
        }

//        //300 - works fine, just takes forever
//        foreach(StateDump::$dumpables as $dumpable) {
//            $dumpable::factory(300)->create();
//        }
//
//        $new = StateDump::dump();
//
//        foreach(StateDump::$dumpables as $dumpable) {
//            self::assertArrayHasKey($dumpable, $new->data);
//        }
    }

    public function testFlagSets()
    {
        //creates set it
        Cache::clear();
        self::assertFalse(Cache::has(StateDump::SHOULD_DUMP));
        foreach(StateDump::$dumpables as $dumpable) {
            $dumpable::factory()->create();
        }
        self::assertTrue(Cache::has(StateDump::SHOULD_DUMP));

        //updates too
        Cache::clear();
        self::assertFalse(Cache::has(StateDump::SHOULD_DUMP));
        foreach(StateDump::$dumpables as $dumpable) {
            $dumpable::first()->update($dumpable::factory()->make()->toArray());
        }
        self::assertTrue(Cache::has(StateDump::SHOULD_DUMP));

        Cache::clear();
    }
}
