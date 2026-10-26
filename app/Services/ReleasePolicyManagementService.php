<?php

namespace App\Services;

use App\Enums\ActivityAction as Action;
use App\Models\EspacioAcademico;
use App\Models\PoliticaLiberacion;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
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

        $audit = new ActivityAttempt($actor);

        return $this->security->change($actor, 'liberacion.politicas.gestionar', function () use ($actor, $data, $audit) {
            $audit->admit(Action::POLITICA_LIBERACION_CREADA);
            $policy = new PoliticaLiberacion;
            $policy->forceFill($data)->save();

            app(ActivityRecorder::class)->success(Action::POLITICA_LIBERACION_CREADA, $actor, [], ['id' => (int) $policy->id]);

            return $policy;
        }, 'version', $audit);
    }

    public function assign(Usuario $actor, int $spaceId, ?int $policyId): void
    {
        $audit = new ActivityAttempt($actor);
        $this->security->change($actor, 'liberacion.politicas.gestionar', function () use ($actor, $spaceId, $policyId, $audit) {
            $space = EspacioAcademico::whereKey($spaceId)->lockForUpdate()->firstOrFail();
            if ($space->politica_liberacion_id === $policyId) {
                return;
            }
            $previous = (int) ($space->politica_liberacion_id ?? 0);
            if ($policyId !== null && ! DB::table('politicas_liberacion')->where('id', $policyId)->exists()) {
                throw ValidationException::withMessages(['politica_id' => 'Seleccione una versión activa y válida.']);
            }
            $audit->admit($policyId === null ? Action::POLITICA_RETIRADA_ESPACIO : Action::POLITICA_ASIGNADA_ESPACIO, ['recurso_id' => (int) $spaceId]);
            $this->assertNoCommitments($this->commitments([$spaceId]), $audit);
            if ($policyId !== null) {
                $policy = DB::table('politicas_liberacion')->where('id', $policyId)->sharedLock()->first();
                if (! $policy || ! $policy->activa || ! $this->rules->valid($policy)) {
                    $audit->reject(ValidationException::withMessages(['politica_id' => 'Seleccione una versión activa y válida.']));
                }
            }
            $space->forceFill(['politica_liberacion_id' => $policyId])->save();
            app(ActivityRecorder::class)->success($audit->action, $actor, $audit->context, ['anterior_id' => $previous, 'nuevo_id' => (int) ($policyId ?? 0)]);
        }, audit: $audit);
    }

    public function state(Usuario $actor, int $policyId, bool $active): void
    {
        $audit = new ActivityAttempt($actor);
        $this->security->change($actor, 'liberacion.politicas.gestionar', function () use ($actor, $policyId, $active, $audit) {
            // RBAC serializes human assignments; current reads avoid the security snapshot.
            $spaces = $active ? DB::table('espacios_academicos')->where('politica_liberacion_id', $policyId)->orderBy('recurso_id')->lockForUpdate()->pluck('recurso_id')->all() : [];
            $commitments = $active ? $this->commitments($spaces) : collect();
            $policy = DB::table('politicas_liberacion')->where('id', $policyId)->lockForUpdate()->first();
            abort_unless($policy, 404);
            if ((bool) $policy->activa === $active) {
                return;
            }
            $audit->admit($active ? Action::POLITICA_LIBERACION_ACTIVADA : Action::POLITICA_LIBERACION_DESACTIVADA);
            if ($active) {
                if (! $this->rules->valid($policy)) {
                    $audit->reject(ValidationException::withMessages(['activa' => 'La configuración de esta versión no es válida.']));
                }
                $this->assertNoCommitments($commitments, $audit);
            }
            DB::table('politicas_liberacion')->where('id', $policyId)->update(['activa' => $active]);
            app(ActivityRecorder::class)->success($audit->action, $actor, [], ['id' => $policyId]);
        }, audit: $audit);
    }

    private function commitments(array $spaces): Collection
    {
        // Current read before acquiring policy, never reserve-after-policy. Space locks
        // exclude approvals; CU23 backs off with NOWAIT instead of inverting this order.
        return DB::table('reservas')->whereIn('espacio_id', $spaces)->where('estado', 'APROBADA')->where('fin', '>', CarbonImmutable::now('America/La_Paz'))->orderBy('id')->sharedLock()->get(['fin']);
    }

    private function assertNoCommitments(Collection $commitments, ActivityAttempt $audit): void
    {
        if ($commitments->contains(fn ($r) => $r->fin > CarbonImmutable::now('America/La_Paz')->toDateTimeString())) {
            $audit->reject(ValidationException::withMessages(['politica_id' => 'Hay reservas aprobadas vigentes o futuras. No se puede cambiar ni reactivar la política.']));
        }
    }
}
