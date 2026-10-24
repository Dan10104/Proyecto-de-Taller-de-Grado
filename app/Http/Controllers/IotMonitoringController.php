<?php

namespace App\Http\Controllers;

use App\Enums\TipoEventoOcupacion;
use App\Exceptions\IotException;
use App\Models\EventoOcupacion;
use App\Models\SensorIot;
use App\Services\IotMonitoringService;
use App\Services\IotPayload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class IotMonitoringController extends Controller
{
    public function __invoke(Request $request, IotMonitoringService $service, IotPayload $payload)
    {
        $filters = $request->validate(['sensor_id' => ['nullable', 'integer', 'min:1'], 'espacio_id' => ['nullable', 'integer', 'min:1'], 'tipo' => ['nullable', Rule::enum(TipoEventoOcupacion::class)], 'desde' => ['nullable', 'string', 'max:25'], 'hasta' => ['nullable', 'string', 'max:25']]);
        $dates = [];
        foreach (['desde', 'hasta'] as $key) {
            if (! empty($filters[$key])) {
                try {
                    $dates[$key] = $payload->date($filters[$key]);
                } catch (IotException) {
                    throw ValidationException::withMessages([$key => 'Use una fecha válida con segundos y zona (ej.: 2026-10-23T10:00:00-04:00).']);
                }
            }
        }
        if (isset($dates['desde'], $dates['hasta']) && $dates['hasta']->lte($dates['desde'])) {
            throw ValidationException::withMessages(['hasta' => 'El fin debe ser posterior al inicio.']);
        }
        $sensors = SensorIot::with('espacioActual.recurso')->when($filters['sensor_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($filters['espacio_id'] ?? null, fn ($q, $id) => $q->where('espacio_actual_id', $id))->orderBy('id')->paginate(15, ['*'], 'sensores_page')->withQueryString();
        $current = $service->current($sensors->pluck('espacio_actual_id')->unique()->all(), $sensors->pluck('id')->all());
        $sensors->through(fn ($s) => [...$s->toArray(), 'comunicacion' => $service->communication($s), 'lecturas' => array_values(array_filter($current[$s->espacio_actual_id] ?? [], fn ($r) => $r['sensor_id'] === $s->id))]);
        $events = EventoOcupacion::with(['sensor:id,identificador', 'espacioHistorico.recurso:id,codigo,nombre'])
            ->when($filters['sensor_id'] ?? null, fn ($q, $id) => $q->where('sensor_id', $id))->when($filters['espacio_id'] ?? null, fn ($q, $id) => $q->where('espacio_historico_id', $id))
            ->when($filters['tipo'] ?? null, fn ($q, $type) => $q->where('tipo', $type))->when($dates['desde'] ?? null, fn ($q, $date) => $q->where('fecha_evento', '>=', $date))
            ->when($dates['hasta'] ?? null, fn ($q, $date) => $q->where('fecha_evento', '<', $date))->orderByDesc('fecha_evento')->orderByDesc('fecha_recepcion')->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Iot/Monitoring', ['sensores' => $sensors, 'eventos' => $events, 'filtros' => $filters, 'consultado' => now()->format('Y-m-d H:i:s'), 'ventana' => config('iot.monitoring_freshness_seconds')]);
    }
}
