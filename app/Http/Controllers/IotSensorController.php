<?php

namespace App\Http\Controllers;

use App\Enums\EstadoSensorIot;
use App\Models\EspacioAcademico;
use App\Models\SensorIot;
use App\Services\IotManagementService;
use App\Services\IotMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IotSensorController extends Controller
{
    private function spaces()
    {
        return EspacioAcademico::with('recurso:id,codigo,nombre')->orderBy('recurso_id')->get(['recurso_id']);
    }

    public function index(Request $request, IotMonitoringService $monitor)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'estado' => ['nullable', Rule::enum(EstadoSensorIot::class)], 'espacio_id' => ['nullable', 'integer']]);
        $sensors = SensorIot::with('espacioActual.recurso')->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('identificador', 'like', '%'.$search.'%')->orWhere('tipo', 'like', '%'.$search.'%')))
            ->when($filters['estado'] ?? null, fn ($q, $state) => $q->where('estado', $state))->when($filters['espacio_id'] ?? null, fn ($q, $id) => $q->where('espacio_actual_id', $id))
            ->orderBy('id')->paginate(15)->withQueryString()->through(fn ($s) => [...$s->toArray(), 'comunicacion' => $monitor->communication($s)]);

        return Inertia::render('Iot/Sensors', ['sensores' => $sensors, 'filtros' => $filters, 'espacios' => $this->spaces()]);
    }

    public function create()
    {
        return Inertia::render('Iot/Form', ['sensor' => null, 'espacios' => $this->spaces()]);
    }

    public function store(Request $request, IotManagementService $service)
    {
        $sensor = $service->save($request->user(), null, $request->all());

        return redirect($request->user()->can('iot.sensores.ver') ? '/iot/sensores/'.$sensor->id : '/inicio')->with('success', 'Sensor registrado. Provisione la credencial para configurar el dispositivo.');
    }

    public function show(SensorIot $sensor, IotMonitoringService $monitor)
    {
        return Inertia::render('Iot/Show', ['sensor' => $sensor->load('espacioActual.recurso'), 'comunicacion' => $monitor->communication($sensor)]);
    }

    public function edit(SensorIot $sensor)
    {
        return Inertia::render('Iot/Form', ['sensor' => $sensor, 'espacios' => $this->spaces()]);
    }

    public function update(Request $request, SensorIot $sensor, IotManagementService $service)
    {
        $service->save($request->user(), $sensor, $request->all());

        return redirect($request->user()->can('iot.sensores.ver') ? '/iot/sensores/'.$sensor->id : '/inicio')->with('success', 'Sensor actualizado. Si cambió el espacio, actualice también el código configurado en el dispositivo.');
    }

    public function state(Request $request, SensorIot $sensor, IotManagementService $service)
    {
        $data = $request->validate(['estado' => ['required', Rule::enum(EstadoSensorIot::class)]]);
        $service->state($request->user(), $sensor, $data['estado']);

        return back()->with('success', 'Estado del sensor actualizado. El historial se conserva.');
    }

    public function provision(Request $request, SensorIot $sensor, IotManagementService $service)
    {
        // Direct JSON response, never an Inertia prop, flash or persistent download.
        return response()->json(['credential' => $service->provision($request->user(), $sensor)], 200, ['Cache-Control' => 'private, no-store, max-age=0']);
    }
}
