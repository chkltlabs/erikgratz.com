<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Content\SiteSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TerminalSnapshotController extends Controller
{
    public function __invoke(SiteSnapshot $snapshot): JsonResponse
    {
        return response()->json($snapshot->toArray());
    }
}
