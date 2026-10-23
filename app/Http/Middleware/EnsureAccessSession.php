<?php

namespace App\Http\Middleware;

use App\Services\AccessSessionService;
use Closure;
use Illuminate\Http\Request;

class EnsureAccessSession
{
    public function handle(Request $request, Closure $next)
    {
        $control = app(AccessSessionService::class);
        if (! $request->user() || ! $control->validateAndRenew($request)) {
            $control->clear($request);

            return redirect()->route('login')->with('notice', 'La sesión finalizó. Inicie sesión nuevamente.');
        }

        return $next($request);
    }
}
