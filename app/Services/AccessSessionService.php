<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Models\SesionAcceso;
use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccessSessionService
{
    public function expires(): CarbonInterface
    {
        $minutes = (int) config('session.lifetime');
        if ($minutes < 1) {
            throw new \RuntimeException('Duración de sesión no válida.');
        }

        return now()->addMinutes($minutes);
    }

    public function register(Request $request, Usuario $user): void
    {
        DB::transaction(function () use ($request, $user) {
            $current = Usuario::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($current->estado !== EstadoUsuario::ACTIVO) {
                throw new \RuntimeException('Acceso no disponible.');
            }
            (new SesionAcceso)->forceFill(['usuario_id' => $user->id, 'token_hash' => hash('sha256', $request->session()->getId()),
                'creada_en' => now(), 'expira_en' => $this->expires(), 'revocada_en' => null])->save();
        });
    }

    public function validateAndRenew(Request $request): bool
    {
        return DB::transaction(function () use ($request) {
            $user = Usuario::whereKey($request->user()->id)->lockForUpdate()->first();
            if (! $user || $user->estado !== EstadoUsuario::ACTIVO) {
                return false;
            }
            $session = SesionAcceso::where('usuario_id', $user->id)->where('token_hash', hash('sha256', $request->session()->getId()))->lockForUpdate()->first();
            if (! $session || $session->revocada_en || ! $session->expira_en->isAfter(now())) {
                return false;
            }
            $session->forceFill(['expira_en' => $this->expires()])->save();

            return true;
        });
    }

    public function revoke(Request $request): void
    {
        $hashes = [hash('sha256', $request->session()->getId())];
        if ($pending = $request->session()->get('pending_logout_hash')) {
            $hashes[] = $pending;
        }
        SesionAcceso::whereIn('token_hash', $hashes)->whereNull('revocada_en')->update(['revocada_en' => now()]);
    }

    public function clear(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
