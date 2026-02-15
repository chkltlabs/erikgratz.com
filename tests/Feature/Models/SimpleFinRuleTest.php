<?php

namespace Tests\Feature\Models;

use App\Models\PeriodicSpend;
use App\Models\SimpleFinRule;
use App\Models\Spend;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimpleFinRuleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_can_be_created_with_factory(): void
    {
        $rule = SimpleFinRule::factory()->create([
            'pattern' => 'Test Pattern',
        ]);

        $this->assertDatabaseHas('simple_fin_rules', [
            'id' => $rule->id,
            'pattern' => 'Test Pattern',
        ]);
    }

    public function test_it_belongs_to_a_spend(): void
    {
        $spend = Spend::factory()->create(['name' => 'Test Spend']);
        $rule = SimpleFinRule::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
        ]);

        $this->assertInstanceOf(Spend::class, $rule->spend);
        $this->assertEquals($spend->id, $rule->spend->id);
        $this->assertEquals('Test Spend', $rule->spend->name);
    }

    public function test_it_belongs_to_a_periodic_spend(): void
    {
        $periodicSpend = PeriodicSpend::factory()->create(['name' => 'Test Periodic Spend']);
        $rule = SimpleFinRule::factory()->create([
            'spend_type' => 'periodic_spend',
            'spend_id' => $periodicSpend->id,
        ]);

        $this->assertInstanceOf(PeriodicSpend::class, $rule->spend);
        $this->assertEquals($periodicSpend->id, $rule->spend->id);
        $this->assertEquals('Test Periodic Spend', $rule->spend->name);
    }

    public function test_spend_can_have_rules(): void
    {
        $spend = Spend::factory()->create();
        $rule = SimpleFinRule::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'pattern' => 'SPEND-PATTERN',
        ]);

        $this->assertTrue($spend->rules()->where('pattern', 'SPEND-PATTERN')->exists());
    }

    public function test_periodic_spend_can_have_rules(): void
    {
        $periodicSpend = PeriodicSpend::factory()->create();
        $rule = SimpleFinRule::factory()->create([
            'spend_type' => 'periodic_spend',
            'spend_id' => $periodicSpend->id,
            'pattern' => 'PERIODIC-PATTERN',
        ]);

        $this->assertTrue($periodicSpend->rules()->where('pattern', 'PERIODIC-PATTERN')->exists());
    }
}
