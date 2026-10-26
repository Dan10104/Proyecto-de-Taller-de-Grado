<?php

namespace App\Services;

use App\Enums\ResultadoEvaluacionLiberacion as Result;
use App\Exceptions\ReleaseDeferredException;
use App\Models\EvaluacionLiberacion;
use App\Services\Notifications\NotificationActivityRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReleaseEvaluationService
{
    public function __construct(private ReleasePolicyRules $rules, private ReleaseDecision $decision) {}

    public function evaluate(int $reservationId): ?EvaluacionLiberacion
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('CU23 requiere una transacción nueva sin snapshot previo.');
        }
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(fn () => $this->lockedEvaluation($reservationId));
            } catch (QueryException $error) {
                if (! in_array((int) ($error->errorInfo[1] ?? 0), [1205, 1213, 3572], true)) {
                    throw $error;
                }
            } catch (ReleaseDeferredException) {
                // A clock boundary during persistence also requires a complete rollback.
            }
            if ($attempt < 3) {
                usleep(20000 * $attempt); // Outside DB::transaction, after rollback.
            }
        }
        throw new ReleaseDeferredException;
    }

    private function lockedEvaluation(int $id): ?EvaluacionLiberacion
    {
        $reservation = DB::table('reservas')->where('id', $id)->lock('FOR UPDATE NOWAIT')->first();
        if (! $reservation || $reservation->estado !== 'APROBADA') {
            return null;
        }
        $space = DB::table('espacios_academicos')->where('recurso_id', $reservation->espacio_id)->lock('FOR UPDATE NOWAIT')->first();
        if (! $space) {
            throw new LogicException('Contexto de reserva no resoluble.');
        }
        if ($space->politica_liberacion_id === null) {
            return null;
        }
        $policy = DB::table('politicas_liberacion')->where('id', $space->politica_liberacion_id)->lock('FOR SHARE NOWAIT')->first();
        if (! $policy) {
            throw new LogicException('Política de reserva no resoluble.');
        }
        $sensors = DB::table('sensores_iot')->forceIndex('idx_sensor_espacio')->where('espacio_actual_id', $space->recurso_id)->where('estado', 'HABILITADO')->orderBy('id')->lock('FOR SHARE NOWAIT')->pluck('id');

        // The first consistent read is below, AFTER every current locking read.
        // The space and complete sensor inventory protect the committed evidence cut.
        for ($cut = 0; $cut < 3; $cut++) {
            $time = $this->now();
            if (! $this->eligible($reservation, $policy, $time)) {
                return null;
            }
            $history = DB::table('evaluaciones_liberacion')->where('reserva_id', $id)->where('politica_id', $policy->id);
            if ((clone $history)->where('resultado', '!=', 'NO_DETERMINABLE')->exists()) {
                return null;
            }
            $last = (clone $history)->orderByDesc('fecha_hora')->orderByDesc('id')->first();
            if ($last && $time->toDateTimeString() < $last->fecha_hora) {
                return null;
            }
            if (! $policy->activa || ! $this->rules->valid($policy)) {
                if ($last) {
                    return null; // Config diagnostics never loop on ignored telemetry.
                }
                $qr = $events = collect();
                $result = Result::NO_DETERMINABLE;
                $reason = 'La política está inactiva o su configuración no es válida. No se consultó evidencia QR/IoT para decidir; la reserva se conserva.';
            } else {
                [$qr, $events] = $this->evidence($reservation, $policy, $sensors, $time);
                if ($last && ! $this->hasNovelty($reservation, $policy, $last, $qr, $events)) {
                    return null;
                }
                [$result, $reason] = $this->decision->decide($policy, $qr->contains('resultado', 'VALIDA'), $this->decision->classify($sensors, $events));
            }
            if (! $time->eq($this->now())) {
                continue; // Re-query the same snapshot using the refreshed temporal window.
            }
            $evaluation = new EvaluacionLiberacion;
            $evaluation->forceFill(['reserva_id' => $id, 'politica_id' => $policy->id, 'fecha_hora' => $time, 'resultado' => $result, 'motivo' => $reason])->save();
            $this->attach('evaluaciones_validaciones_qr', 'validacion_id', $evaluation->id, $qr);
            $this->attach('evaluaciones_eventos_ocupacion', 'evento_id', $evaluation->id, $events);
            if (! $time->eq($this->now()) || ! $this->eligible($reservation, $policy, $this->now())) {
                throw new ReleaseDeferredException;
            }
            if ($result === Result::LIBERAR) {
                $updated = DB::table('reservas')->where('id', $id)->where('estado', 'APROBADA')->update(['estado' => 'LIBERADA']);
                if ($updated !== 1) {
                    throw new LogicException('La transición de liberación no se confirmó.');
                }
            }

            if (in_array($result, [Result::ALERTAR, Result::LIBERAR], true)) {
                app(NotificationActivityRecorder::class)->release($evaluation);
            }

            return $evaluation;
        }
        throw new ReleaseDeferredException;
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now('America/La_Paz')->startOfSecond();
    }

    private function eligible(object $reservation, object $policy, CarbonImmutable $time): bool
    {
        $start = CarbonImmutable::parse($reservation->inicio, 'America/La_Paz')->timestamp;
        $end = CarbonImmutable::parse($reservation->fin, 'America/La_Paz')->timestamp;
        $tolerance = filter_var($policy->tolerancia_min, FILTER_VALIDATE_INT);
        $seconds = $tolerance !== false && $tolerance >= 0 && $tolerance <= 2147483647 ? $tolerance * 60 : 0;

        return $time->timestamp >= $start && $time->timestamp < $end && $seconds < $end - $start && $time->timestamp - $start >= $seconds;
    }

    private function evidence(object $r, object $p, Collection $sensors, CarbonImmutable $time): array
    {
        $qr = DB::table('validaciones_qr')->where('reserva_id', $r->id)->where('recurso_esperado_id', $r->espacio_id)->where('tipo_operacion', 'USO_ESPACIO')->whereBetween('fecha_hora', [$r->inicio, $time->toDateTimeString()])->orderBy('id')->get(['id', 'resultado', 'fecha_hora']);
        $seconds = $p->usa_iot ? $p->max_antiguedad_lectura_min * 60 : 300;
        $start = CarbonImmutable::parse($r->inicio, 'America/La_Paz');
        // Bound subtraction by the reservation duration, including INT_MAX policies.
        $window = $time->subSeconds(min($seconds, $time->timestamp - $start->timestamp));
        $events = DB::table('eventos_ocupacion')->whereIn('sensor_id', $sensors)->where('espacio_historico_id', $r->espacio_id)->whereBetween('fecha_evento', [$window->toDateTimeString(), $time->toDateTimeString()])->where('fecha_recepcion', '<=', $time->toDateTimeString())->orderBy('id')->get(['id', 'sensor_id', 'tipo', 'valor', 'fecha_evento', 'fecha_recepcion']);

        return [$qr, $events];
    }

    private function hasNovelty(object $r, object $p, object $last, Collection $qr, Collection $events): bool
    {
        foreach ([['evaluaciones_validaciones_qr', 'validacion_id', $qr, ['fecha_hora']], ['evaluaciones_eventos_ocupacion', 'evento_id', $events, ['fecha_recepcion', 'fecha_evento']]] as [$table, $column, $items, $dates]) {
            $possible = $items->filter(fn ($item) => collect($dates)->contains(fn ($date) => $last->fecha_hora <= $item->$date));
            foreach ($possible->chunk(1000) as $chunk) {
                $used = DB::table($table.' as pivot')->join('evaluaciones_liberacion as evaluation', 'evaluation.id', '=', 'pivot.evaluacion_id')->where('evaluation.reserva_id', $r->id)->where('evaluation.politica_id', $p->id)->whereIn('pivot.'.$column, $chunk->pluck('id'))->pluck('pivot.'.$column);
                if ($chunk->pluck('id')->diff($used)->isNotEmpty()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function attach(string $table, string $column, int $evaluationId, Collection $evidence): void
    {
        foreach ($evidence->chunk(1000) as $chunk) {
            DB::table($table)->insert($chunk->map(fn ($item) => ['evaluacion_id' => $evaluationId, $column => $item->id])->values()->all());
        }
    }
}
