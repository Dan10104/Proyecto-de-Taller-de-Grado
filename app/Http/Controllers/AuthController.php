<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AccessSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function create(Request $request)
    {
        return Auth::check() ? redirect()->route('inicio') : Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request, AccessSessionService $sessions)
    {
        if (Auth::check()) {
            return redirect()->route('inicio');
        }
        $key = 'login:'.hash('sha256', $request->string('correo').'|'.$request->ip());
        $ipKey = 'login-ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, config('security.login_attempts')) || RateLimiter::tooManyAttempts($ipKey, config('security.login_ip_attempts'))) {
            throw ValidationException::withMessages(['correo' => 'Demasiados intentos. Espere un minuto e inténtelo nuevamente.']);
        }
        RateLimiter::hit($ipKey, config('security.login_decay_seconds'));
        if (! Auth::attempt(['correo' => $request->input('correo'), 'password' => $request->input('password'), 'estado' => 'ACTIVO'])) {
            RateLimiter::hit($key, config('security.login_decay_seconds'));
            throw ValidationException::withMessages(['correo' => 'Las credenciales proporcionadas no son válidas.']);
        }
        try {
            $request->session()->regenerate(true);
            $sessions->register($request, $request->user());
            $request->session()->save();
        } catch (\Throwable) {
            try {
                $sessions->revoke($request);
            } catch (\Throwable) {
            }
            try {
                $sessions->clear($request);
            } catch (\Throwable) {
            }

            return response('El servicio de acceso no está disponible temporalmente. Intente nuevamente.', 503);
        }
        RateLimiter::clear($key);

        return redirect()->route('inicio');
    }

    public function destroy(Request $request, AccessSessionService $sessions)
    {
        try {
            $sessions->revoke($request);
        } catch (\Throwable) {
            // Keep only a server-side revocation reference after invalidating authentication.
            $pending = $request->session()->get('pending_logout_hash') ?? hash('sha256', $request->session()->getId());
            $sessions->clear($request);
            $request->session()->put('pending_logout_hash', $pending);

            return response('No fue posible confirmar el cierre completo. Intente nuevamente.', 503);
        }
        $sessions->clear($request);
        Inertia::clearHistory();

        return redirect()->route('login')->with('notice', 'Sesión cerrada correctamente.');
    }
}
