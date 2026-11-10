<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\PublicFitAgent;
use App\Livewire\Page\Home;
use App\Models\Contact;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TerminalSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['terminal.token' => 'test-terminal-token']);
    }

    #[Test]
    public function snapshot_requires_the_terminal_token(): void
    {
        $this->getJson('/api/terminal/snapshot')->assertUnauthorized();

        $this->withHeader('X-Terminal-Token', 'nope')
            ->getJson('/api/terminal/snapshot')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The terminal token was rejected.');

        config(['terminal.token' => '']);

        $this->withHeader('X-Terminal-Token', '')
            ->getJson('/api/terminal/snapshot')
            ->assertUnauthorized();
    }

    #[Test]
    public function snapshot_returns_the_public_site_only(): void
    {
        $photo = Photo::factory()->create([
            'title' => 'Dock at dusk',
            'path' => 'photos/dock.jpg',
            'description' => 'A long exposure.',
            'tags' => ['water'],
        ]);
        $photo->forceFill(['url' => 'https://cdn.example/dock.jpg'])->save();
        Photo::factory()->create([
            'title' => 'Storage path',
            'path' => 'photos/storage.jpg',
        ]);

        $response = $this->withHeader('X-Terminal-Token', 'test-terminal-token')
            ->getJson('/api/terminal/snapshot');

        $response->assertOk()
            ->assertJsonPath('identity.name', 'Erik V Gratz')
            ->assertJsonPath('experience.0.company', 'Pocketnest')
            ->assertJsonPath('experience.1.title', 'Developer')
            ->assertJsonPath('portfolio.0.title', 'Laravel Porthole')
            ->assertJsonPath('photos.0.title', 'Dock at dusk')
            ->assertJsonPath('photos.0.url', 'https://cdn.example/dock.jpg')
            ->assertJsonPath('photos.0.tags.0', 'water')
            ->assertJsonPath('photos.1.url', url('/storage/photos/storage.jpg'))
            ->assertJsonPath('contact.email', config('contact.email'));

        $payload = $response->json();
        $this->assertSame(
            ['identity', 'experience', 'portfolio', 'photos', 'contact'],
            array_keys($payload),
        );
        $this->assertStringNotContainsString('The Media Lab', (string) json_encode($payload));
    }

    #[Test]
    public function home_reads_work_history(): void
    {
        $jerbs = Livewire::test(Home::class)->instance()->jerbs();

        $this->assertSame('Pocketnest', $jerbs[0]['company']);
        $this->assertSame('Developer', $jerbs[1]['title']);
        $this->assertSame('Internet Things', $jerbs[2]['company']);
        $this->assertSame('PHP Developer', $jerbs[2]['title']);

        $this->get('/home')
            ->assertOk()
            ->assertSee('Pocketnest')
            ->assertSee('Senior Software Engineer, Backend');
    }

    #[Test]
    public function contact_uses_the_public_form_rules(): void
    {
        $this->withHeader('X-Terminal-Token', 'test-terminal-token')
            ->postJson('/api/terminal/contact', [
                'name' => 'Ada',
                'message' => 'Hello from the terminal.',
            ])
            ->assertUnprocessable();

        $this->withHeader('X-Terminal-Token', 'test-terminal-token')
            ->postJson('/api/terminal/contact', [
                'name' => 'Ada',
                'contact' => 'ada@example.com',
                'message' => 'Hello from the terminal.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'contact request sent!');

        $this->assertDatabaseHas(Contact::class, [
            'name' => 'Ada',
            'contact' => 'ada@example.com',
        ]);
    }

    #[Test]
    public function fit_uses_the_public_assistant(): void
    {
        PublicFitAgent::fake(fn (): string => 'Pocketnest is the closest match.');

        $this->withHeader('X-Terminal-Token', 'test-terminal-token')
            ->postJson('/api/terminal/fit', [
                'question' => 'What did you build at Pocketnest?',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Pocketnest is the closest match.');

        PublicFitAgent::assertPrompted(fn ($prompt) => $prompt->contains('What did you build at Pocketnest?'));
    }

    #[Test]
    public function fit_failures_stay_json(): void
    {
        PublicFitAgent::fake(function (): never {
            throw new \RuntimeException('provider down');
        });

        $this->withHeader('X-Terminal-Token', 'test-terminal-token')
            ->postJson('/api/terminal/fit', [
                'question' => 'What did you build at Pocketnest?',
            ])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Something went wrong answering that. Please try again.');
    }
}
