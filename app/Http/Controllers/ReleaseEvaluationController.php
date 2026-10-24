<?php

namespace App\Http\Controllers;

use App\Enums\ResultadoEvaluacionLiberacion;
use App\Models\EvaluacionLiberacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReleaseEvaluationController extends Controller
{
    private function scope(Request $request): array
    {
        $data = $request->validate(['alcance' => ['nullable', Rule::in(['propias', 'administrativas'])]]);
        $scope = $data['alcance'] ?? ($request->user()->can('liberacion.evaluaciones.ver_administrativas') ? 'administrativas' : 'propias');
        Gate::authorize('liberacion.evaluaciones.ver_'.$scope);
        $query = EvaluacionLiberacion::with(['reserva.espacio.recurso:id,codigo,nombre', 'politica:id,nombre,version'])->when($scope === 'propias', fn ($q) => $q->whereHas('reserva', fn ($q) => $q->where('solicitante_id', $request->user()->id)));

        return [$scope, $query];
    }

    private function summary(EvaluacionLiberacion $e): array
    {
        // Explicit projection shared by list/detail; never serialize reservation owners.
        return ['id' => $e->id, 'fecha_hora' => $e->fecha_hora->format('Y-m-d H:i:s'), 'resultado' => $e->resultado->value, 'motivo' => $e->motivo,
            'reserva' => ['id' => $e->reserva->id, 'codigo' => $e->reserva->codigo, 'estado' => $e->reserva->estado->value],
            'espacio' => ['codigo' => $e->reserva->espacio->recurso->codigo, 'nombre' => $e->reserva->espacio->recurso->nombre],
            'politica' => ['id' => $e->politica->id, 'nombre' => $e->politica->nombre, 'version' => $e->politica->version]];
    }

    public function index(Request $request)
    {
        [$scope, $query] = $this->scope($request);
        $filters = $request->validate(['resultado' => ['nullable', Rule::enum(ResultadoEvaluacionLiberacion::class)], 'politica_id' => ['nullable', 'integer'], 'reserva_id' => ['nullable', 'integer']]);
        foreach ($filters as $field => $value) {
            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        return Inertia::render('Release/Evaluations', ['evaluaciones' => $query->orderByDesc('fecha_hora')->orderByDesc('id')->paginate(15)->withQueryString()->through(fn ($e) => $this->summary($e)), 'alcance' => $scope, 'filtros' => $filters]);
    }

    public function show(Request $request, int $evaluacion)
    {
        [$scope, $query] = $this->scope($request);
        $e = $query->findOrFail($evaluacion);
        $props = ['evaluacion' => $this->summary($e), 'alcance' => $scope];
        if ($scope === 'administrativas') {
            $props['validaciones'] = $e->validaciones()->with('validadoPor:id,nombres,apellidos')->orderBy('validaciones_qr.id')->paginate(20, ['validaciones_qr.id', 'fecha_hora', 'resultado', 'validado_por_id'], 'qr_page')->withQueryString()->through(fn ($q) => ['fecha_hora' => $q->fecha_hora->format('Y-m-d H:i:s'), 'resultado' => $q->resultado->value, 'actor' => $q->validadoPor->nombres.' '.$q->validadoPor->apellidos]);
            $props['eventos'] = $e->eventos()->with('sensor:id,identificador')->orderBy('eventos_ocupacion.id')->paginate(20, ['eventos_ocupacion.id', 'sensor_id', 'tipo', 'valor', 'fecha_evento', 'fecha_recepcion'], 'iot_page')->withQueryString()->through(fn ($event) => ['sensor' => $event->sensor->identificador, 'tipo' => $event->tipo->value, 'valor' => $event->valor, 'fecha_evento' => $event->fecha_evento->format('Y-m-d H:i:s'), 'fecha_recepcion' => $event->fecha_recepcion->format('Y-m-d H:i:s')]);
        }

        return Inertia::render('Release/EvaluationShow', $props);
    }
}
