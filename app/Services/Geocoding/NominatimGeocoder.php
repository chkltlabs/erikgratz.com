<?php

declare(strict_types=1);

namespace App\Services\Geocoding;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NominatimGeocoder
{
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    private const RATE_LIMIT_KEY = 'nominatim:last-request-at';

    /**
     * Search for place-like locations. Results are cached aggressively; the public
     * Nominatim API is only hit after an explicit search and at most once per second.
     *
     * @return list<array{place_id: string, display_name: string, latitude: float, longitude: float}>
     */
    public function search(string $query): array
    {
        $normalized = $this->normalizeQuery($query);

        if ($normalized === '') {
            return [];
        }

        $cacheKey = 'nominatim:search:'.sha1($normalized);

        /** @var list<array{place_id: string, display_name: string, latitude: float, longitude: float}> */
        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($normalized): array {
            $this->throttle();

            $baseUrl = rtrim((string) config('services.nominatim.base_url'), '/');
            $userAgent = (string) config('services.nominatim.user_agent');
            $email = config('services.nominatim.email');

            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept-Language' => 'en',
                ])
                ->get("{$baseUrl}/search", array_filter([
                    'q' => $normalized,
                    'format' => 'jsonv2',
                    'addressdetails' => 0,
                    'limit' => 8,
                    'featuretype' => 'city',
                    'email' => filled($email) ? $email : null,
                ]));

            Cache::put(self::RATE_LIMIT_KEY, microtime(true), 10);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Nominatim search failed with status '.$response->status()
                );
            }

            /** @var array<int, array<string, mixed>> $payload */
            $payload = $response->json() ?? [];

            return collect($payload)
                ->filter(fn (array $item): bool => isset($item['lat'], $item['lon'], $item['display_name']))
                ->map(fn (array $item): array => [
                    'place_id' => (string) ($item['place_id'] ?? $item['osm_id'] ?? sha1($item['display_name'])),
                    'display_name' => (string) $item['display_name'],
                    'latitude' => (float) $item['lat'],
                    'longitude' => (float) $item['lon'],
                ])
                ->values()
                ->all();
        });
    }

    protected function normalizeQuery(string $query): string
    {
        return Str::of($query)->squish()->lower()->toString();
    }

    protected function throttle(): void
    {
        $lastRequestAt = Cache::get(self::RATE_LIMIT_KEY);

        if ($lastRequestAt === null) {
            return;
        }

        $elapsed = microtime(true) - (float) $lastRequestAt;
        $wait = 1.05 - $elapsed;

        if ($wait > 0) {
            usleep((int) round($wait * 1_000_000));
        }
    }
}
