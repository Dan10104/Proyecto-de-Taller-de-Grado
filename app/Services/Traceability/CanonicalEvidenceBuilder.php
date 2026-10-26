<?php

namespace App\Services\Traceability;

use App\Models\RegistroActividad;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CanonicalEvidenceBuilder
{
    public function __construct(private TraceabilityCanonicalizer $jcs, private TraceabilityHashService $hashes) {}

    public function hash(RegistroActividad $activity): string
    {
        return $this->hashes->digest($this->jcs->serialize($this->build($activity)));
    }

    public function build(RegistroActividad $activity): array
    {
        $a = $activity->getAttributes();
        if (! $a['critica'] || $a['resultado'] !== 'EXITO' || ! $a['actor_id'] || $a['proceso_responsable'] !== null || $a['incidencia_id'] !== null) {
            throw new TraceabilityUnavailable('INVALID_ACTIVITY');
        }
        $subject = match ($a['accion']) {
            'RESERVA_APROBADA' => $this->reservation($a, true),
            'RESERVA_CANCELADA' => $this->reservation($a, false),
            'PRESTAMO_ENTREGADO' => $this->loan($a, true),
            'PRESTAMO_DEVUELTO' => $this->loan($a, false),
            default => throw new TraceabilityUnavailable('UNSUPPORTED_OPERATION'),
        };

        return ['activity_id' => $this->id($a['id']), 'occurred_at' => $this->date($a['fecha_hora']),
            'operation' => $a['accion'], 'schema' => 'espacio-u-trace', 'subject' => $subject, 'version' => '1'];
    }

    private function reservation(array $a, bool $approval): array
    {
        if (! $a['reserva_id'] || $a['prestamo_id']) {
            throw new TraceabilityUnavailable('INVALID_CONTEXT');
        }
        $r = DB::table('reservas')->find($a['reserva_id']);
        $actor = $approval ? 'resuelta_por_id' : 'cancelada_por_id';
        $date = $approval ? 'resuelta_en' : 'cancelada_en';
        if (! $r || ! $r->$actor || ! $r->$date) {
            throw new TraceabilityUnavailable('MISSING_HISTORY');
        }
        if ($a['actor_id'] != $r->$actor || $a['fecha_hora'] !== $r->$date || $r->espacio_id != $a['recurso_id'] || $r->solicitante_id != $a['usuario_afectado_id']) {
            throw new TraceabilityUnavailable('HISTORICAL_MISMATCH');
        }
        $base = ['reserva_id' => $this->id($r->id), 'reserva_codigo' => $r->codigo, 'espacio_id' => $this->id($r->espacio_id)];

        return $approval ? $base + ['inicio' => $this->date($r->inicio), 'fin' => $this->date($r->fin), 'resuelta_por_id' => $this->id($r->$actor), 'resuelta_en' => $this->date($r->$date)]
            : $base + ['cancelada_por_id' => $this->id($r->$actor), 'cancelada_en' => $this->date($r->$date), 'motivo_cancelacion_hash' => $this->secondary($r->motivo_cancelacion)];
    }

    private function loan(array $a, bool $delivery): array
    {
        if (! $a['prestamo_id'] || $a['reserva_id']) {
            throw new TraceabilityUnavailable('INVALID_CONTEXT');
        }
        $p = DB::table('prestamos')->find($a['prestamo_id']);
        $h = DB::table($delivery ? 'entregas_prestamo' : 'devoluciones_prestamo')->where('prestamo_id', $a['prestamo_id'])->first();
        if (! $p || ! $h) {
            throw new TraceabilityUnavailable('MISSING_HISTORY');
        }
        $actor = $delivery ? $h->entregado_por_id : $h->confirmado_por_id;
        $date = $delivery ? $h->fecha_hora : $h->confirmada_en;
        if (! $actor || ! $date || (! $delivery && $h->estado !== 'CONFIRMADA')) {
            throw new TraceabilityUnavailable('MISSING_HISTORY');
        }
        if ($actor != $a['actor_id'] || $date !== $a['fecha_hora'] || $p->equipamiento_id != $a['recurso_id'] || $p->solicitante_id != $a['usuario_afectado_id']) {
            throw new TraceabilityUnavailable('HISTORICAL_MISMATCH');
        }
        $base = ['prestamo_id' => $this->id($p->id), 'prestamo_codigo' => $p->codigo, 'equipamiento_id' => $this->id($p->equipamiento_id)];
        if ($delivery) {
            if ($h->receptor_id != $p->solicitante_id || $h->condicion_entrega === null) {
                throw new TraceabilityUnavailable('MISSING_HISTORY');
            }

            return $base + ['entrega_id' => $this->id($h->id), 'entregado_por_id' => $this->id($actor), 'receptor_id' => $this->id($h->receptor_id), 'fecha_hora' => $this->date($date), 'condicion_entrega_hash' => $this->secondary($h->condicion_entrega), 'observacion_hash' => $this->secondary($h->observacion)];
        }

        return $base + ['devolucion_id' => $this->id($h->id), 'recibido_por_id' => $this->id($h->recibido_por_id), 'confirmado_por_id' => $this->id($actor), 'registrada_en' => $this->date($h->registrada_en), 'confirmada_en' => $this->date($date), 'condicion' => $h->condicion, 'descripcion_condicion_hash' => $this->secondary($h->descripcion_condicion), 'observacion_hash' => $this->secondary($h->observacion)];
    }

    public function secondary(?string $text): ?string
    {
        return $text === null ? null : $this->hashes->digest($text);
    }

    private function id(mixed $id): string
    {
        if (! preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            throw new TraceabilityUnavailable('MISSING_HISTORY');
        }

        return (string) $id;
    }

    private function date(?string $value): string
    {
        if (! $value) {
            throw new TraceabilityUnavailable('MISSING_HISTORY');
        }

        return CarbonImmutable::parse($value, 'America/La_Paz')->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
