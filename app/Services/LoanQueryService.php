<?php

namespace App\Services;

use App\Models\EquipamientoTecnologico;
use App\Models\Prestamo;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class LoanQueryService
{
    public function scoped(Usuario $actor, bool $admin): Builder
    {
        Gate::forUser($actor)->authorize($admin ? 'prestamos.ver_administrativos' : 'prestamos.ver_propios');

        return Prestamo::query()->when(! $admin, fn ($q) => $q->where('solicitante_id', $actor->id));
    }

    public function filtered(Usuario $actor, bool $admin, array $filters): Builder
    {
        $q = $this->scoped($actor, $admin);
        foreach (['estado', 'equipamiento_id'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, $filters[$field]);
            }
        }
        if ($admin && ! empty($filters['solicitante_id'])) {
            $q->where('solicitante_id', $filters['solicitante_id']);
        }
        if (! empty($filters['vencidos'])) {
            $q->where('estado', 'ENTREGADO')->where('fin_previsto', '<', now());
        }
        if (! empty($filters['revision'])) {
            $q->whereHas('devolucion', fn ($q) => $q->where('estado', 'PENDIENTE_REVISION'));
        }
        if (! empty($filters['fecha'])) {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $filters['fecha'], 'America/La_Paz');
            $q->where('fin_previsto', '>', $start);
            if ($filters['fecha'] !== '9999-12-31') {
                $q->where('inicio_previsto', '<', $start->addDay());
            }
        }
        if (! empty($filters['q'])) {
            $text = $filters['q'];
            $q->where(fn ($q) => $q->where('codigo', 'like', "%$text%")->orWhereHas('equipamiento.recurso', fn ($q) => $q->where('nombre', 'like', "%$text%")));
        }

        return $q->with(['equipamiento.recurso', 'equipamiento.tipo', 'equipamiento.espacioBase.recurso', 'equipamiento.espacioBase.piso', 'solicitante:id,nombres,apellidos', 'devolucion']);
    }

    public function options(Usuario $actor, bool $admin): array
    {
        $scope = $this->scoped($actor, $admin);

        return ['equipamientos' => EquipamientoTecnologico::whereIn('recurso_id', (clone $scope)->select('equipamiento_id'))->with('recurso')->orderBy('recurso_id')->get()->map(fn ($e) => ['id' => $e->getKey(), 'nombre' => $e->recurso->codigo.' · '.$e->recurso->nombre]),
            'solicitantes' => $admin ? Usuario::whereIn('id', (clone $scope)->select('solicitante_id'))->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']) : []];
    }

    public function equipment(EquipamientoTecnologico $e): array
    {
        return ['id' => $e->getKey(), 'codigo' => $e->recurso->codigo, 'nombre' => $e->recurso->nombre, 'tipo' => $e->tipo->nombre, 'estado' => $e->estado->value, 'marca' => $e->marca, 'modelo' => $e->modelo, 'ubicacion' => $e->espacioBase ? $e->espacioBase->piso->nombre.' · '.$e->espacioBase->recurso->nombre : 'Sin espacio base'];
    }

    public function present(Prestamo $p, bool $admin, array $permissions, bool $detail = false): array
    {
        $review = $p->devolucion?->estado->value === 'PENDIENTE_REVISION';
        $result = ['id' => $p->id, 'codigo' => $p->codigo, 'equipamiento' => $this->equipment($p->equipamiento), 'estado' => $p->estado->value, 'inicio_previsto' => $p->inicio_previsto->format('Y-m-d H:i'), 'fin_previsto' => $p->fin_previsto->format('Y-m-d H:i'), 'motivo' => $p->motivo, 'vencido' => $p->vencido(), 'revision_pendiente' => $review, 'acciones' => [
            'resolver' => $p->estado->value === 'PENDIENTE' && in_array('prestamos.resolver', $permissions, true),
            'entregar' => $p->estado->value === 'APROBADO' && in_array('prestamos.registrar_entrega', $permissions, true),
            'devolver' => $p->estado->value === 'ENTREGADO' && ! $p->devolucion && in_array('prestamos.registrar_devolucion', $permissions, true),
            'revisar' => $review && in_array('prestamos.registrar_devolucion', $permissions, true)]];
        if ($admin) {
            $result['solicitante'] = $p->solicitante->only(['nombres', 'apellidos']);
        }
        if ($detail) {
            $person = fn ($v) => $v?->only(['nombres', 'apellidos']);
            $result += ['solicitado_en' => $p->solicitado_en->format('Y-m-d H:i:s'), 'resuelto_en' => $p->resuelto_en?->format('Y-m-d H:i:s'), 'resuelto_por' => $person($p->resueltoPor), 'motivo_rechazo' => $p->motivo_rechazo,
                'entrega' => $p->entrega ? ['fecha_hora' => $p->entrega->fecha_hora->format('Y-m-d H:i:s'), 'condicion_entrega' => $p->entrega->condicion_entrega, 'observacion' => $p->entrega->observacion, 'entregado_por' => $person($p->entrega->entregadoPor), 'receptor' => $person($p->entrega->receptor), 'recepcion_confirmada_en' => null] : null,
                'devolucion' => $p->devolucion ? ['estado' => $p->devolucion->estado->value, 'condicion' => $p->devolucion->condicion->value, 'descripcion_condicion' => $p->devolucion->descripcion_condicion, 'observacion' => $p->devolucion->observacion, 'registrada_en' => $p->devolucion->registrada_en->format('Y-m-d H:i:s'), 'confirmada_en' => $p->devolucion->confirmada_en?->format('Y-m-d H:i:s'), 'recibido_por' => $person($p->devolucion->recibidoPor), 'confirmado_por' => $person($p->devolucion->confirmadoPor)] : null];
        }

        return $result;
    }
}
