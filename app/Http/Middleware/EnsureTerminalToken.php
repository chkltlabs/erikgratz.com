<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTerminalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('terminal.token');
        $given = (string) $request->header('X-Terminal-Token', '');

        if ($configured === '' || ! hash_equals($configured, $given)) {
            abort(401);
        }

        return $next($request);
    }
}
