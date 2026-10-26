<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use App\Services\Notifications\NotificationReadService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [...parent::share($request),
            'auth' => function () use ($request) {
                if (! $request->user()) {
                    return null;
                }
                $permissions = app(AuthorizationService::class)->permissions($request->user());

                return ['usuario' => $request->user()->only(['id', 'nombres', 'apellidos']),
                    'roles' => $request->user()->rolesActivos()->pluck('nombre')->all(), 'permisos' => $permissions,
                    'no_leidas' => in_array('notificaciones.ver_propias', $permissions, true) ? app(NotificationReadService::class)->unread($request->user()) : 0];
            },
            'flash' => ['success' => fn () => $request->session()->get('success'), 'notice' => fn () => $request->session()->get('notice')],
        ];
    }
}
