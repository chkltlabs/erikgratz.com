<?php

namespace Tests\Feature\Geocoding;

use App\Models\User;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(route('admin.location-search', ['q' => 'Berlin']))
            ->assertUnauthorized();
    }

    public function test_validates_query(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('admin.location-search', ['q' => 'a']))
            ->assertStatus(422);
    }

    public function test_returns_search_results(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'place_id' => 123,
                    'display_name' => 'Berlin, Germany',
                    'lat' => '52.520008',
                    'lon' => '13.404954',
                ],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create());

        $this->getJson(route('admin.location-search', ['q' => 'Berlin']))
            ->assertOk()
            ->assertJsonPath('data.0.display_name', 'Berlin, Germany')
            ->assertJsonPath('data.0.latitude', 52.520008)
            ->assertJsonPath('data.0.longitude', 13.404954);

        Http::assertSentCount(1);
    }

    public function test_caches_repeated_searches(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'place_id' => 1,
                    'display_name' => 'Paris, France',
                    'lat' => '48.8566',
                    'lon' => '2.3522',
                ],
            ], 200),
        ]);

        $geocoder = app(NominatimGeocoder::class);

        $first = $geocoder->search('Paris');
        $second = $geocoder->search('  PARIS ');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_handles_upstream_failure(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response('error', 500),
        ]);

        $this->actingAs(User::factory()->create());

        $this->getJson(route('admin.location-search', ['q' => 'Nowhere']))
            ->assertStatus(502)
            ->assertJsonPath('message', 'Location search is temporarily unavailable.');
    }
}
