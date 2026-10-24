<?php

namespace App\Services;

use App\Enums\ResultadoEvaluacionLiberacion as Result;
use Illuminate\Support\Collection;

class ReleaseDecision
{
    public function classify(Collection $sensorIds, Collection $events): string
    {
        $positive = false;
        $zero = false;
        $ambiguous = false;
        $covered = [];
        $instants = [];
        foreach ($events as $event) {
            $key = $event->sensor_id.'|'.$event->tipo.'|'.$event->fecha_evento;
            $value = $event->valor === null ? 'NULL' : (string) (float) $event->valor;
            if (isset($instants[$key]) && $instants[$key] !== $value) {
                return 'contradictoria';
            }
            $instants[$key] = $value;
            if ($event->tipo === 'PRESENCIA' || ($event->valor !== null && (float) $event->valor > 0)) {
                $positive = true;
            } elseif ($event->tipo === 'OCUPACION' && $event->valor !== null && (float) $event->valor === 0.0) {
                $zero = true;
                $covered[$event->sensor_id] = true;
            } else {
                $ambiguous = true;
            }
        }
        if ($positive && $zero) {
            return 'contradictoria';
        }
        if ($positive) {
            return 'positiva';
        }
        if (! $ambiguous && $sensorIds->isNotEmpty() && $sensorIds->every(fn ($id) => isset($covered[$id]))) {
            return 'negativa';
        }

        return 'insuficiente';
    }

    public function decide(object $policy, bool $validQr, string $iot): array
    {
        if ($iot === 'contradictoria' || ($validQr && $iot === 'negativa')) {
            return [Result::NO_DETERMINABLE, 'Las evidencias de uso son contradictorias. La reserva se conserva.'];
        }
        if ($validQr || $iot === 'positiva') {
            return [Result::MANTENER_POR_USO, 'Existe evidencia válida de uso dentro del periodo evaluado. La reserva se conserva.'];
        }
        if ($policy->usa_iot && $iot !== 'negativa') {
            return [Result::NO_DETERMINABLE, 'La evidencia IoT requerida es insuficiente para todos los sensores habilitados. La reserva se conserva.'];
        }
        $reason = $policy->usa_iot ? 'Sin validación QR válida y con evidencia IoT negativa completa.' : 'No se registró el QR obligatorio después de la tolerancia y no existe veto de uso. No constituye una medición de espacio vacío.';

        return $policy->modo === 'AUTOMATICA'
            ? [Result::LIBERAR, $reason.' La política automática libera la reserva.']
            : [Result::ALERTAR, $reason.' Requiere atención; el modo alerta conserva la reserva.'];
    }
}
