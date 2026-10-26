<?php

namespace App\Services\Traceability;

use App\Models\EvidenciaTrazabilidad;
use App\Models\RegistroActividad;
use Illuminate\Database\QueryException;

final class LocalEvidenceService
{
    public const VERSION = 'espacio-u-jcs-v1';

    public function __construct(private CanonicalEvidenceBuilder $builder) {}

    public function ensure(int $activityId): EvidenciaTrazabilidad
    {
        $existing = EvidenciaTrazabilidad::where('registro_actividad_id', $activityId)->first();
        if ($existing) {
            return $existing;
        }
        $hash = $this->builder->hash(RegistroActividad::findOrFail($activityId));
        try {
            $evidence = new EvidenciaTrazabilidad;
            $evidence->forceFill(['registro_actividad_id' => $activityId, 'algoritmo_hash' => 'SHA-256', 'hash' => $hash,
                'version_canonica' => self::VERSION, 'registrada_localmente_en' => now(), 'estado' => 'PENDIENTE', 'resultado_verificacion' => 'PENDIENTE'])->save();

            return $evidence->fresh();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062 && ($winner = EvidenciaTrazabilidad::where('registro_actividad_id', $activityId)->first())) {
                return $winner;
            }
            throw new TraceabilityUnavailable('LOCAL_INSERT_FAILED');
        }
    }

    public function check(EvidenciaTrazabilidad $evidence): string
    {
        if ($evidence->version_canonica !== self::VERSION || $evidence->algoritmo_hash !== 'SHA-256' || ! preg_match('/^[0-9a-f]{64}$/D', $evidence->hash)) {
            return 'NO_VERIFICABLE';
        }
        try {
            return hash_equals($evidence->hash, $this->builder->hash($evidence->actividad()->firstOrFail())) ? 'VALIDO' : 'INCONSISTENTE';
        } catch (TraceabilityUnavailable $error) {
            return $error->reason === 'HISTORICAL_MISMATCH' ? 'INCONSISTENTE' : 'NO_VERIFICABLE';
        } catch (\Throwable) {
            return 'NO_VERIFICABLE';
        }
    }
}
