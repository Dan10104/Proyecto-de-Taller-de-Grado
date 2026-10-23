<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [...parent::share($request),
            'auth' => fn () => $request->user() ? ['usuario' => $request->user()->only(['id', 'nombres', 'apellidos']),
                'roles' => $request->user()->rolesActivos()->pluck('nombre')->all(), 'permisos' => app(AuthorizationService::class)->permissions($request->user())] : null,
            'flash' => ['success' => fn () => $request->session()->get('success'), 'notice' => fn () => $request->session()->get('notice')],
        ];
    }
}
