<?php

namespace App\Services;

use App\Exceptions\EquipmentConflictException;
use App\Http\Requests\IncidentRequest;
use App\Models\DevolucionPrestamo;
use App\Models\EquipamientoTecnologico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Usuario;
use App\Services\Notifications\NotificationActivityRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class IncidentMaintenanceService
{
    public function __construct(private ResourceOperationService $operations) {}

    public function open(Usuario $actor, array $data): IncidenciaMantenimiento
    {
        $data = IncidentRequest::normalize($data);
        Validator::make($data, IncidentRequest::inputRules())->validate();
        $equipment = EquipamientoTecnologico::whereKey($data['recurso_id'])->exists();

        return $this->operations->transaction($actor, 'incidencias.gestionar', function ($actor) use ($data, $equipment) {
            $context = $this->operations->lock((int) $data['recurso_id'], $equipment);
            $resource = $context['resource'];
            $state = $equipment ? ($resource->estado->value === 'BAJA' ? 'BAJA' : 'EN_MANTENIMIENTO') : ($data['estado_espacio'] ?? 'EN_MANTENIMIENTO');
            if ($state !== $resource->estado->value) {
                Gate::forUser($actor)->authorize($this->operations->statePermission($resource));
            }
            $incident = new IncidenciaMantenimiento;
            $incident->forceFill(['recurso_id' => $resource->getKey(), 'reportado_por_id' => $actor->id, 'tipo' => $data['tipo'], 'descripcion' => $data['descripcion'], 'inicio' => CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['inicio'], 'America/La_Paz'), 'fin_previsto' => empty($data['fin_previsto']) ? null : CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['fin_previsto'], 'America/La_Paz'), 'estado' => 'ABIERTA'])->save();
            $this->operations->setState($resource, $state);

            app(NotificationActivityRecorder::class)->incident($incident, false);

            return $incident;
        });
    }

    public function close(Usuario $actor, IncidenciaMantenimiento $target, array $data): IncidenciaMantenimiento
    {
        $data = IncidentRequest::normalize($data);
        Validator::make($data, IncidentRequest::closeRules())->validate();
        // These are locators only; every relationship is checked again under the fixed lock order.
        $equipment = EquipamientoTecnologico::whereKey($target->recurso_id)->exists();
        $loanId = $target->devolucion_id ? DevolucionPrestamo::findOrFail($target->devolucion_id)->prestamo_id : null;

        return $this->operations->transaction($actor, 'incidencias.cerrar', function ($actor) use ($target, $data, $equipment, $loanId) {
            $context = $this->operations->lock($target->recurso_id, $equipment, $loanId, $target->id);
            $incident = $context['incidents']->firstWhere('id', $target->id);
            if (! $incident || $incident->estado->value !== 'ABIERTA') {
                throw new EquipmentConflictException('La incidencia ya fue cerrada o cambió de recurso.');
            }
            if ($incident->devolucion_id && (! $equipment || ! $context['returns']->contains('id', $incident->devolucion_id))) {
                throw new EquipmentConflictException('La devolución asociada no corresponde a este recurso.');
            }
            if (now()->lessThan($incident->inicio)) {
                throw new EquipmentConflictException('El cierre no puede ser anterior al inicio registrado.');
            }
            $changes = ['estado' => 'CERRADA', 'cerrado_por_id' => $actor->id, 'cerrada_en' => now(), 'solucion' => $data['solucion']];
            if (IncidenciaMantenimiento::whereKey($incident->id)->where('estado', 'ABIERTA')->update($changes) !== 1) {
                throw new EquipmentConflictException('La incidencia ya cambió.');
            }
            $incident->forceFill($changes)->syncOriginal();
            $this->operations->restore($actor, $context, ! empty($data['apto']));

            app(NotificationActivityRecorder::class)->incident($incident, true);

            return $incident;
        });
    }
}
