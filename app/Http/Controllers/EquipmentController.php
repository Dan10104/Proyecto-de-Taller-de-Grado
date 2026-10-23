<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentFilterRequest;
use App\Http\Requests\EquipmentRequest;
use App\Http\Requests\EquipmentStateRequest;
use App\Http\Requests\EquipmentTypeRequest;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\TipoEquipamiento;
use App\Services\EquipmentAvailabilityService;
use App\Services\EquipmentService;
use App\Services\LoanQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    private function options(): array
    {
        return ['tipos' => TipoEquipamiento::orderBy('nombre')->get(), 'espacios' => EspacioAcademico::with(['recurso', 'piso'])->orderBy('recurso_id')->get()->map(fn ($e) => ['id' => $e->getKey(), 'nombre' => $e->recurso->nombre.' · '.$e->piso->nombre]), 'pisos' => Piso::orderBy('numero')->get(['id', 'nombre'])];
    }

    public function index(EquipmentFilterRequest $request, EquipmentAvailabilityService $service)
    {
        $filters = $request->validated();
        $availability = $request->routeIs('equipamientos.availability');
        $complete = ! empty($filters['inicio_previsto']) && ! empty($filters['fin_previsto']);
        $q = $complete ? $service->candidates($filters, ...$service->period($filters['inicio_previsto'], $filters['fin_previsto'])) : $service->matching($filters);

        return Inertia::render('Equipment/Index', ['equipamientos' => $availability && ! $complete ? null : $q->orderBy('recurso_id')->paginate(15)->withQueryString()->through(fn ($e) => app(LoanQueryService::class)->equipment($e)), 'filtros' => $filters, 'disponibilidad' => $availability, 'periodoCompleto' => $complete, 'opciones' => $this->options()]);
    }

    public function create()
    {
        Gate::authorize('create', EquipamientoTecnologico::class);

        return Inertia::render('Equipment/Form', ['equipamiento' => null, 'opciones' => $this->options()]);
    }

    public function store(EquipmentRequest $request, EquipmentService $service)
    {
        $e = $service->save($request->user(), null, $request->validated());

        return $this->redirect($request, $e, 'Equipamiento registrado.');
    }

    public function edit(EquipamientoTecnologico $equipamiento)
    {
        Gate::authorize('update', $equipamiento);

        return Inertia::render('Equipment/Form', ['equipamiento' => $equipamiento->load('recurso'), 'opciones' => $this->options()]);
    }

    public function update(EquipmentRequest $request, EquipamientoTecnologico $equipamiento, EquipmentService $service)
    {
        $service->save($request->user(), $equipamiento, $request->validated());

        return $this->redirect($request, $equipamiento, 'Equipamiento actualizado.');
    }

    private function redirect(Request $r, EquipamientoTecnologico $e, string $message)
    {
        return redirect($r->user()->can('view', $e) ? '/equipamientos/'.$e->getKey() : '/inicio')->with('success', $message);
    }

    public function show(Request $request, EquipamientoTecnologico $equipamiento, LoanQueryService $query)
    {
        Gate::authorize('view', $equipamiento);
        $equipamiento->load(['recurso', 'tipo', 'espacioBase.recurso', 'espacioBase.piso']);

        return Inertia::render('Equipment/Show', ['equipamiento' => [...$query->equipment($equipamiento), ...$equipamiento->only(['descripcion', 'numero_serie', 'referencia_ubicacion'])],
            'incidenciasAbiertas' => $equipamiento->incidencias()->where('estado', 'ABIERTA')->count()]);
    }

    public function state(EquipmentStateRequest $request, EquipamientoTecnologico $equipamiento, EquipmentService $service)
    {
        $service->state($request->user(), $equipamiento, $request->validated('estado'), $request->boolean('apto'));

        return back()->with('success', 'Estado operativo actualizado.');
    }

    public function types(Request $request)
    {
        return Inertia::render('Equipment/Types', ['tipos' => TipoEquipamiento::orderBy('nombre')->paginate(15)->withQueryString()]);
    }

    public function saveType(EquipmentTypeRequest $request, EquipmentService $service, ?TipoEquipamiento $tipo = null)
    {
        $service->saveType($request->user(), $tipo, $request->validated());

        return back()->with('success','Tipo de equipamiento guardado.');
    }
}
