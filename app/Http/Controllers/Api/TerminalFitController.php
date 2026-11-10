<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\Ai\UserSafeAiException;
use App\Http\Controllers\Controller;
use App\Services\Ai\PublicFitAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalFitController extends Controller
{
    public function __invoke(Request $request, PublicFitAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'job_description' => ['nullable', 'string'],
        ]);

        try {
            $result = $assistant->ask(
                question: $validated['question'],
                jobDescription: $validated['job_description'] ?? null,
                ip: $request->ip(),
            );
        } catch (UserSafeAiException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Something went wrong answering that. Please try again.',
            ], 500);
        }

        return response()->json($result);
    }
}
