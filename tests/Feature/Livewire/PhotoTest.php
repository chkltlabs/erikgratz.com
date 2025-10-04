<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Page\Photo;
use App\Models\Photo as PhotoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

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

    /** @test */
    public function it_has_correct_title_constant()
    {
        $this->assertEquals('Photo', Photo::TITLE);
    }

    /** @test */
    public function it_can_render_the_component()
    {
        Livewire::test(Photo::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.page.photo');
    }

    /** @test */
    public function it_loads_all_photos_correctly()
    {
        $component = Livewire::test(Photo::class);

        $photos = $component->get('photos');

        $this->assertCount(3, $photos);
        $this->assertEquals('Nature Photo', $photos[0]['title']);
        $this->assertEquals('Portrait Photo', $photos[1]['title']);
        $this->assertEquals('Mixed Photo', $photos[2]['title']);
    }

    /** @test */
    public function it_generates_unique_tags_with_all_option()
    {
        $component = Livewire::test(Photo::class);

        $tags = $component->get('tags');

        $this->assertContains('all', $tags);
        $this->assertContains('nature', $tags);
        $this->assertContains('landscape', $tags);
        $this->assertContains('portrait', $tags);
        $this->assertContains('people', $tags);

        // Should have 'all' + 4 unique tags = 5 total
        $this->assertCount(5, $tags);

        // 'all' should be first
        $this->assertEquals('all', $tags[0]);
    }

    /** @test */
    public function it_initializes_with_all_tag_selected()
    {
        Livewire::test(Photo::class)
            ->assertSet('selectedTag', 'all');
    }

    /** @test */
    public function it_can_filter_by_tag()
    {
        Livewire::test(Photo::class)
            ->call('filterByTag', 'nature')
            ->assertSet('selectedTag', 'nature');
    }

    /** @test */
    public function filtered_photos_returns_all_when_all_selected()
    {
        $component = Livewire::test(Photo::class);

        $filteredPhotos = $component->get('filteredPhotos');

        $this->assertCount(3, $filteredPhotos);
    }

    /** @test */
    public function filtered_photos_filters_by_nature_tag()
    {
        $component = Livewire::test(Photo::class)
            ->call('filterByTag', 'nature');

        $filteredPhotos = $component->get('filteredPhotos');

        $this->assertCount(2, $filteredPhotos);

        // Check that both photos contain the 'nature' tag
        foreach ($filteredPhotos as $photo) {
            $this->assertContains('nature', $photo['tags']);
        }
    }

    /** @test */
    public function filtered_photos_filters_by_portrait_tag()
    {
        $component = Livewire::test(Photo::class);
        $component->call('filterByTag', 'portrait');

        $filteredPhotos = $component->get('filteredPhotos');

        $this->assertCount(1, $filteredPhotos);
        $this->assertEquals('Portrait Photo', array_first($filteredPhotos)['title']);
    }

    /** @test */
    public function filtered_photos_filters_by_people_tag()
    {
        $component = Livewire::test(Photo::class)
            ->call('filterByTag', 'people');

        $filteredPhotos = $component->get('filteredPhotos');

        $this->assertCount(2, $filteredPhotos);

        // Check that both photos contain the 'people' tag
        foreach ($filteredPhotos as $photo) {
            $this->assertContains('people', $photo['tags']);
        }
    }

    /** @test */
    public function filtered_photos_returns_empty_for_non_existent_tag()
    {
        $component = Livewire::test(Photo::class)
            ->call('filterByTag', 'nonexistent');

        $filteredPhotos = $component->get('filteredPhotos');

        $this->assertCount(0, $filteredPhotos);
    }

    /** @test */
    public function component_is_reactive_to_tag_changes()
    {
        Livewire::test(Photo::class)
            ->assertSet('selectedTag', 'all')
            ->call('filterByTag', 'landscape')
            ->assertSet('selectedTag', 'landscape')
            ->call('filterByTag', 'all')
            ->assertSet('selectedTag', 'all');
    }

    /** @test */
    public function photos_computed_property_returns_array_format()
    {
        $component = Livewire::test(Photo::class);

        $photos = $component->get('photos');

        $this->assertIsArray($photos);

        foreach ($photos as $photo) {
            $this->assertArrayHasKey('title', $photo);
            $this->assertArrayHasKey('tags', $photo);
            $this->assertArrayHasKey('url', $photo);
            $this->assertArrayHasKey('path', $photo);
            $this->assertArrayHasKey('description', $photo);
        }
    }

    /** @test */
    public function tags_computed_property_handles_empty_photos()
    {
        // Clear all photos
        PhotoModel::truncate();

        $component = Livewire::test(Photo::class);

        $tags = $component->get('tags');

        $this->assertEquals(['all'], $tags);
    }

    /** @test */
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
