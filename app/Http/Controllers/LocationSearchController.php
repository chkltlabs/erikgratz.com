<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class LocationSearchController extends Controller
{
    public function __invoke(Request $request, NominatimGeocoder $geocoder): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        try {
            $results = $geocoder->search($validated['q']);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Location search is temporarily unavailable.',
            ], 502);
        }

        return response()->json([
            'data' => $results,
        ]);
    }
}
