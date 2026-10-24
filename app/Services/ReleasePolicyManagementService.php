<?php

namespace App\Services;

use App\Models\EspacioAcademico;
use App\Models\PoliticaLiberacion;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleasePolicyManagementService
{
    public function __construct(private SpaceManagementService $security, private ReleasePolicyRules $rules) {}

    public function create(Usuario $actor, array $data): PoliticaLiberacion
    {
        if (is_string($data['nombre'] ?? null)) {
            $data['nombre'] = trim($data['nombre']);
        }
        $data = $this->rules->validator($data)->validate();
        $data['max_antiguedad_lectura_min'] ??= null;

        return $this->security->change($actor, 'liberacion.politicas.gestionar', function () use ($data) {
            $policy = new PoliticaLiberacion;
            $policy->forceFill($data)->save();

            return $policy;
        }, 'version');
    }

    public function assign(Usuario $actor, int $spaceId, ?int $policyId): void
    {
        $this->security->change($actor, 'liberacion.politicas.gestionar', function () use ($spaceId, $policyId) {
            $space = EspacioAcademico::whereKey($spaceId)->lockForUpdate()->firstOrFail();
            if ($space->politica_liberacion_id === $policyId) {
                return;
            }
            $this->assertNoCommitments($this->commitments([$spaceId]));
            if ($policyId !== null) {
                $policy = DB::table('politicas_liberacion')->where('id', $policyId)->sharedLock()->first();
                if (! $policy || ! $policy->activa || ! $this->rules->valid($policy)) {
                    throw ValidationException::withMessages(['politica_id' => 'Seleccione una versión activa y válida.']);
                }
            }
            $space->forceFill(['politica_liberacion_id' => $policyId])->save();
        });
    }

    public function state(Usuario $actor, int $policyId, bool $active): void
    {
        $this->security->change($actor, 'liberacion.politicas.gestionar', function () use ($policyId, $active) {
            // RBAC serializes human assignments; current reads avoid the security snapshot.
            $spaces = $active ? DB::table('espacios_academicos')->where('politica_liberacion_id', $policyId)->orderBy('recurso_id')->lockForUpdate()->pluck('recurso_id')->all() : [];
            $commitments = $active ? $this->commitments($spaces) : collect();
            $policy = DB::table('politicas_liberacion')->where('id', $policyId)->lockForUpdate()->first();
            abort_unless($policy, 404);
            if ((bool) $policy->activa === $active) {
                return;
            }
            if ($active) {
                if (! $this->rules->valid($policy)) {
                    throw ValidationException::withMessages(['activa' => 'La configuración de esta versión no es válida.']);
                }
                $this->assertNoCommitments($commitments);
            }
            DB::table('politicas_liberacion')->where('id', $policyId)->update(['activa' => $active]);
        });
    }

    private function commitments(array $spaces): Collection
    {
        // Current read before acquiring policy, never reserve-after-policy. Space locks
        // exclude approvals; CU23 backs off with NOWAIT instead of inverting this order.
        return DB::table('reservas')->whereIn('espacio_id', $spaces)->where('estado', 'APROBADA')->where('fin', '>', CarbonImmutable::now('America/La_Paz'))->orderBy('id')->sharedLock()->get(['fin']);
    }

    private function assertNoCommitments(Collection $commitments): void
    {
        if ($commitments->contains(fn ($r) => $r->fin > CarbonImmutable::now('America/La_Paz')->toDateTimeString())) {
            throw ValidationException::withMessages(['politica_id' => 'Hay reservas aprobadas vigentes o futuras. No se puede cambiar ni reactivar la política.']);
        }
    }
}
