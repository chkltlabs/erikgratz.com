<?php

namespace Tests\Feature\Models;

use App\Models\StateDump;
use Tests\TestCase;

class StateDumpModelTest extends TestCase
{
    public function testDumping()
    {
        foreach(StateDump::$dumpables as $dumpable) {
            $dumpable::factory(3)->create();
        }

        $new = StateDump::dump();

        foreach(StateDump::$dumpables as $dumpable) {
            self::assertArrayHasKey($dumpable, $new->data);
        }
    }
}
