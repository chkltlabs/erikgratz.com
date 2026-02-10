<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Page\Photo;
use App\Models\Photo as PhotoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    private function createPhotos(): void
    {
        PhotoModel::truncate();
        // Create test photos with different tags
        PhotoModel::factory()->create([
            'title' => 'Nature Photo',
            'tags' => ['nature', 'landscape'],
            'url' => 'https://example.com/nature.jpg',
            'path' => 'photos/nature.jpg',
            'description' => 'Beautiful nature scene'
        ]);

        PhotoModel::factory()->create([
            'title' => 'Portrait Photo',
            'tags' => ['portrait', 'people'],
            'url' => 'https://example.com/portrait.jpg',
            'path' => 'photos/portrait.jpg',
            'description' => 'Portrait photography'
        ]);

        PhotoModel::factory()->create([
            'title' => 'Mixed Photo',
            'tags' => ['nature', 'people'],
            'url' => 'https://example.com/mixed.jpg',
            'path' => 'photos/mixed.jpg',
            'description' => 'Mixed scene'
        ]);
    }

    #[Test]
    public function it_has_correct_title_constant()
    {
        $this->assertEquals('Photo', Photo::TITLE);
    }

    #[Test]
    public function filtered_photos_various_tags()
    {
        $this->createPhotos();
        $component = Livewire::test(Photo::class);

        // All
        $this->assertCount(3, $component->get('filteredPhotos'));

        // Nature
        $component->call('filterByTag', 'nature');
        $filteredPhotos = $component->get('filteredPhotos');
        $this->assertCount(2, $filteredPhotos);
        foreach ($filteredPhotos as $photo) {
            $this->assertContains('nature', $photo['tags']);
        }

        // Portrait
        $component->call('filterByTag', 'portrait');
        $filteredPhotos = $component->get('filteredPhotos');
        $this->assertCount(1, $filteredPhotos);
        $this->assertEquals('Portrait Photo', array_first($filteredPhotos)['title']);

        // People
        $component->call('filterByTag', 'people');
        $filteredPhotos = $component->get('filteredPhotos');
        $this->assertCount(2, $filteredPhotos);
        foreach ($filteredPhotos as $photo) {
            $this->assertContains('people', $photo['tags']);
        }

        // Non-existent
        $component->call('filterByTag', 'nonexistent');
        $this->assertCount(0, $component->get('filteredPhotos'));
    }

    #[Test]
    public function component_properties_and_reactivity()
    {
        $this->createPhotos();
        Livewire::test(Photo::class)
            ->assertSet('selectedTag', 'all')
            ->call('filterByTag', 'landscape')
            ->assertSet('selectedTag', 'landscape')
            ->call('filterByTag', 'all')
            ->assertSet('selectedTag', 'all')
            ->assertStatus(200)
            ->assertViewIs('livewire.page.photo');
    }

    #[Test]
    public function photos_and_tags_computed_properties()
    {
        $this->createPhotos();
        $component = Livewire::test(Photo::class);

        $photos = $component->get('photos');
        $this->assertIsArray($photos);
        $this->assertCount(3, $photos);

        $tags = $component->get('tags');
        $this->assertContains('all', $tags);
        $this->assertCount(5, $tags);
        $this->assertEquals('all', $tags[0]);

        // Empty case
        PhotoModel::truncate();
        \Illuminate\Support\Facades\Cache::forget('photos.all');
        $component = Livewire::test(Photo::class);
        $this->assertEquals(['all'], $component->get('tags'));
    }

    #[Test]
    public function component_uses_correct_layout()
    {
        $reflection = new \ReflectionClass(Photo::class);
        $attributes = $reflection->getAttributes();

        $layoutAttribute = collect($attributes)->first(function ($attribute) {
            return $attribute->getName() === 'Livewire\Attributes\Layout';
        });

        $this->assertNotNull($layoutAttribute);

        $arguments = $layoutAttribute->getArguments();
        $this->assertEquals('livewire.components.layouts.app', $arguments[0]);
        $this->assertEquals(['pageTitle' => 'Photo'], $arguments[1]);
    }
}
