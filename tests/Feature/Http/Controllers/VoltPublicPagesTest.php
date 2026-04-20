<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VoltPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function home_returns_ok(): void
    {
        $this->get(route('home'))->assertOk();
    }

    #[Test]
    public function contact_returns_ok(): void
    {
        $this->get(route('contact'))->assertOk();
    }

    #[Test]
    public function portfolio_returns_ok(): void
    {
        $this->get('/portfolio')->assertOk();
    }

    #[Test]
    public function play_returns_ok_when_enabled(): void
    {
        config(['app.playground_enabled' => true]);

        $this->get('/play')->assertOk();
    }

    #[Test]
    public function play_returns_not_found_when_disabled(): void
    {
        config(['app.playground_enabled' => false]);

        $this->get('/play')->assertNotFound();
    }

    #[Test]
    public function contact_form_persists_contact(): void
    {
        $data = [
            'contact' => 'visitor@example.com',
            'name' => 'Test User',
            'message' => 'Hello from the test suite.',
        ];

        \Livewire\Livewire::test(\App\Livewire\Page\Contact::class)
            ->set($data)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', $data);
    }
}
