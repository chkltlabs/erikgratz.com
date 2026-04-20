<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Page\Experience;
use App\Livewire\Page\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceAndWorkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function experience_component_renders_and_exposes_computed_data(): void
    {
        $component = Livewire::test(Experience::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.page.experience');

        $experience = $component->get('experience');

        $this->assertIsArray($experience);
        $this->assertNotEmpty($experience);
        $this->assertArrayHasKey('company', $experience[0]);
        $this->assertArrayHasKey('title', $experience[0]);
        $this->assertArrayHasKey('timeframe', $experience[0]);
    }

    #[Test]
    public function work_component_renders_and_reads_portfolio_config(): void
    {
        $component = Livewire::test(Work::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.page.work');

        $this->assertSame(config('portfolio'), $component->get('portfolioItems'));
    }

    #[Test]
    public function work_and_experience_routes_return_ok(): void
    {
        $this->get('/work')->assertOk();
        $this->get('/experience')->assertOk();
    }
}
