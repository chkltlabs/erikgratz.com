<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactStoreRequest;
use App\Models\Contact;
use App\Rules\EmailOrPhoneRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalContactController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $rules = ContactStoreRequest::rulesArray();
        $rules['contact'] = ['required', new EmailOrPhoneRule];
        $validated = $request->validate($rules);
        Contact::query()->create($validated);

        return response()->json([
            'message' => 'contact request sent!',
        ]);
    }
}
