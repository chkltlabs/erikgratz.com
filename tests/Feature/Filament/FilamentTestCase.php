<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Tests\TestCase;

class FilamentTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }
}
