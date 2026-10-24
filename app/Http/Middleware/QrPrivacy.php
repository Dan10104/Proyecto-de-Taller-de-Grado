<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class QrPrivacy
{
    public function handle(Request $request, Closure $next)
    {
        // Error pages and reporter traces must never render a QR request or its arguments.
        if ($request->is('qr', 'qr/*', 'reservas/*/qr', 'prestamos/*/qr/*')) {
            config(['app.debug' => false]);
        }

        return $next($request);
    }
}
