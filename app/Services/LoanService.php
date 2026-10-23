<?php

namespace App\Services;

use App\Exceptions\EquipmentConflictException;
use App\Http\Requests\LoanActionRequest;
use App\Http\Requests\LoanRequest;
use App\Models\DevolucionPrestamo;
use App\Models\EntregaPrestamo;
use App\Models\IncidenciaMantenimiento;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class LoanService
{
    public function __construct(private ResourceOperationService $operations, private EquipmentAvailabilityService $availability) {}

    public function create(Usuario $actor, array $data): Prestamo
    {
        $data = LoanRequest::normalize($data);
        Validator::make($data, LoanRequest::inputRules())->validate();
        [$start,$end] = $this->availability->period($data['inicio_previsto'], $data['fin_previsto']);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->operations->transaction($actor, 'prestamos.solicitar', function ($actor) use ($data, $start, $end) {
                    $context = $this->operations->lock((int) $data['equipamiento_id'], true);
                    $this->availability->assertAvailable($context, $start, $end);
                    $loan = new Prestamo;
                    $loan->forceFill(['equipamiento_id' => $context['resource']->getKey(), 'solicitante_id' => $actor->id, 'inicio_previsto' => $start, 'fin_previsto' => $end, 'motivo' => $data['motivo'], 'estado' => 'PENDIENTE', 'solicitado_en' => now()])->save();

                    return $loan;
                });
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062 || ! str_contains($e->getMessage(), 'uq_prestamos_codigo')) {
                    throw $e;
                }
                if ($attempt === 2) {
                    throw new EquipmentConflictException('No se pudo asignar un código único. Inténtelo nuevamente.');
                }
            }
        }
        throw new \LogicException('Código inalcanzable.');
    }

    public function approve(Usuario $actor, Prestamo $target): Prestamo
    {
        return $this->transition($actor, $target, 'prestamos.resolver', function ($actor, $context) {
            $loan = $context['loan'];
            $this->requireState($loan, 'PENDIENTE');
            $this->availability->assertAvailable($context, $loan->inicio_previsto, $loan->fin_previsto, $loan->id);
            $this->update($loan, ['estado' => 'APROBADO', 'resuelto_por_id' => $actor->id, 'resuelto_en' => now(), 'motivo_rechazo' => null]);
        });
    }

    public function reject(Usuario $actor, Prestamo $target, string $reason): Prestamo
    {
        $reason = trim($reason);
        Validator::make(['motivo_rechazo' => $reason], LoanActionRequest::actionRules('reject'))->validate();

        return $this->transition($actor, $target, 'prestamos.resolver', function ($actor, $context) use ($reason) {
            $this->requireState($context['loan'], 'PENDIENTE');
            $this->update($context['loan'], ['estado' => 'RECHAZADO', 'resuelto_por_id' => $actor->id, 'resuelto_en' => now(), 'motivo_rechazo' => $reason]);
        });
    }

    public function deliver(Usuario $actor, Prestamo $target, array $data): Prestamo
    {
        $data = LoanRequest::normalize($data);
        Validator::make($data, LoanActionRequest::actionRules('deliver'))->validate();

        return $this->transition($actor, $target, 'prestamos.registrar_entrega', function ($actor, $context) use ($data) {
            $loan = $context['loan'];
            $this->requireState($loan, 'APROBADO');
            $this->availability->assertAvailable($context, $loan->inicio_previsto, $loan->fin_previsto, $loan->id);
            if ($context['deliveries']->contains('prestamo_id', $loan->id)) {
                throw new EquipmentConflictException('La entrega ya fue registrada.');
            }
            (new EntregaPrestamo)->forceFill(['prestamo_id' => $loan->id, 'entregado_por_id' => $actor->id, 'receptor_id' => $loan->solicitante_id, 'fecha_hora' => now(), 'condicion_entrega' => $data['condicion_entrega'], 'observacion' => $data['observacion'] ?? null, 'recepcion_confirmada_en' => null])->save();
            $this->update($loan, ['estado' => 'ENTREGADO']);
            $this->operations->setState($context['resource'], 'PRESTADO');
        });
    }

    public function receive(Usuario $actor, Prestamo $target, array $data): Prestamo
    {
        return $this->returnOperation($actor, $target, $data, false);
    }

    public function confirmReview(Usuario $actor, Prestamo $target, array $data): Prestamo
    {
        return $this->returnOperation($actor, $target, $data, true);
    }

    private function returnOperation(Usuario $actor, Prestamo $target, array $data, bool $review): Prestamo
    {
        $data = LoanRequest::normalize($data);
        Validator::make($data, LoanActionRequest::actionRules($review ? 'review' : 'receive'))->validate();

        return $this->transition($actor, $target, 'prestamos.registrar_devolucion', function ($actor, $context) use ($data, $review) {
            $loan = $context['loan'];
            $this->requireState($loan, 'ENTREGADO');
            $delivery = $context['deliveries']->firstWhere('prestamo_id', $loan->id);
            if (! $delivery || $delivery->receptor_id !== $loan->solicitante_id) {
                throw new EquipmentConflictException('No existe una entrega válida para este préstamo.');
            }
            $return = $context['returns']->firstWhere('prestamo_id', $loan->id);
            if ($review ? (! $return || $return->estado->value !== 'PENDIENTE_REVISION') : $return !== null) {
                throw new EquipmentConflictException('La devolución ya cambió. Actualice el detalle.');
            }
            $condition = $data['condicion'];
            if ($condition === 'CON_INCIDENCIA') {
                Gate::forUser($actor)->authorize('incidencias.gestionar');
            }
            $pending = $condition === 'NO_DETERMINABLE';
            if ($return && now()->lessThan($return->registrada_en)) {
                throw new EquipmentConflictException('La confirmación no puede ser anterior a la recepción.');
            }
            if (! $return) {
                $return = new DevolucionPrestamo;
                $return->forceFill(['prestamo_id' => $loan->id, 'recibido_por_id' => $actor->id, 'registrada_en' => now()]);
                $context['returns']->push($return);
            }
            $return->forceFill(['condicion' => $condition, 'descripcion_condicion' => $data['descripcion_condicion'] ?? null, 'observacion' => $data['observacion'] ?? $return->observacion, 'estado' => $pending ? 'PENDIENTE_REVISION' : 'CONFIRMADA', 'confirmado_por_id' => $pending ? null : $actor->id, 'confirmada_en' => $pending ? null : now()])->save();
            if ($condition === 'CON_INCIDENCIA') {
                $incident = new IncidenciaMantenimiento;
                $incident->forceFill(['recurso_id' => $loan->equipamiento_id, 'devolucion_id' => $return->id, 'reportado_por_id' => $actor->id, 'tipo' => 'INCIDENCIA', 'descripcion' => $data['descripcion_condicion'], 'inicio' => now(), 'estado' => 'ABIERTA'])->save();
                $context['incidents']->push($incident);
            }
            if (! $pending) {
                $this->update($loan, ['estado' => 'DEVUELTO']);
            }
            $equipment = $context['resource'];
            if ($equipment->estado->value === 'BAJA') {
                return;
            }
            if ($this->operations->restricted($context)) {
                $this->operations->setState($equipment, 'EN_MANTENIMIENTO');

                return;
            }
            $derived = $this->operations->recoveryState($context);
            if ($equipment->estado->value === 'EN_MANTENIMIENTO') {
                if (! empty($data['apto'])) {
                    $this->operations->restore($actor, $context, true);
                }
            } else {
                $this->operations->setState($equipment, $derived);
            }
        });
    }

    private function transition(Usuario $actor, Prestamo $target, string $permission, callable $operation): Prestamo
    {
        return $this->operations->transaction($actor, $permission, function ($actor) use ($target, $operation) {
            $context = $this->operations->lock($target->equipamiento_id, true, $target->id);
            $operation($actor, $context);

            return $context['loan'];
        });
    }

    private function requireState(Prestamo $loan, string $state): void
    {
        if ($loan->estado->value !== $state) {
            throw new EquipmentConflictException('El préstamo ya no admite esta operación. Actualice su estado.');
        }
    }

    private function update(Prestamo $loan, array $changes): void
    {
        if (Prestamo::whereKey($loan->id)->where('estado',$loan->estado->value)->update($changes) !== 1) {
            throw new EquipmentConflictException('El préstamo fue modificado por otra operación.');
        }
        $loan->forceFill($changes)->syncOriginal();
    }
}
