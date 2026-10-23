<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEspacio;
use App\Http\Requests\SpaceBlockRequest;
use App\Http\Requests\SpaceFilterRequest;
use App\Http\Requests\SpaceRequest;
use App\Models\CaracteristicaEspacio;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\TipoEspacio;
use App\Services\SpaceAvailabilityService;
use App\Services\SpaceManagementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SpaceController extends Controller
{
    private function options(): array
    {
        return ['pisos' => Piso::orderBy('numero')->get(), 'tipos' => TipoEspacio::orderBy('nombre')->get(), 'caracteristicas' => CaracteristicaEspacio::orderBy('nombre')->get(), 'estados' => array_column(EstadoEspacio::cases(), 'value')];
    }

    public function index(SpaceFilterRequest $request, SpaceAvailabilityService $service)
    {
        return Inertia::render('Spaces/Index', ['espacios' => $service->matching($request->validated())->orderBy('recurso_id')->paginate(15)->withQueryString(), 'filtros' => $request->validated(), 'opciones' => $this->options()]);
    }

    public function create()
    {
        Gate::authorize('create', EspacioAcademico::class);

        return Inertia::render('Spaces/Form', ['espacio' => null, 'opciones' => $this->options()]);
    }

    public function store(SpaceRequest $request, SpaceManagementService $service)
    {
        $space = $service->save($request->user(), null, $request->validated());

        return redirect()->route($request->user()->can('espacios.ver') ? 'espacios.show' : 'inicio', $request->user()->can('espacios.ver') ? $space : [])->with('success', 'Espacio registrado correctamente.');
    }

    public function show(Request $request, EspacioAcademico $espacio)
    {
        Gate::authorize('view', $espacio);
        $espacio->load(['recurso', 'piso', 'tipo', 'caracteristicas']);
        $manager = $request->user()->can('block', $espacio);
        $blocks = $espacio->bloqueos()->orderByDesc('inicio')->orderByDesc('id');
        if ($manager) {
            $blocks->with('responsable:id,nombres,apellidos');
        } else {
            $blocks->where('activo', true)->where('fin', '>', now());
        }
        $blocks = $blocks->paginate(15)->withQueryString()->through(fn ($b) => [
            'id' => $b->id, 'inicio' => $b->inicio->format('Y-m-d H:i'), 'fin' => $b->fin->format('Y-m-d H:i'), 'activo' => $b->activo,
            ...($manager ? ['motivo' => $b->motivo, 'responsable' => $b->responsable->only(['nombres', 'apellidos'])] : []),
        ]);

        return Inertia::render('Spaces/Show', ['espacio' => $espacio, 'bloqueos' => $blocks, 'estados' => array_column(EstadoEspacio::cases(), 'value')]);
    }

    public function edit(EspacioAcademico $espacio)
    {
        Gate::authorize('update', $espacio);

        return Inertia::render('Spaces/Form', ['espacio' => $espacio->load(['recurso', 'caracteristicas']), 'opciones' => $this->options()]);
    }

    public function update(SpaceRequest $request, EspacioAcademico $espacio, SpaceManagementService $service)
    {
        $service->save($request->user(), $espacio, $request->validated());

        return redirect()->route($request->user()->can('espacios.ver') ? 'espacios.show' : 'inicio', $request->user()->can('espacios.ver') ? $espacio : [])->with('success', 'Espacio actualizado correctamente.');
    }

    public function state(Request $request, EspacioAcademico $espacio, SpaceManagementService $service)
    {
        Gate::authorize('changeState', $espacio);
        $data = $request->validate(['estado' => ['required', Rule::enum(EstadoEspacio::class)]]);
        $service->state($request->user(), $espacio, $data['estado']);

        return back()->with('success', 'Estado operativo actualizado.');
    }

    public function block(SpaceBlockRequest $request, EspacioAcademico $espacio, SpaceManagementService $service)
    {
        $service->block($request->user(), $espacio, $request->validated());

        return back()->with('success', 'Bloqueo registrado. El estado general del espacio se conserva.');
    }

    public function deactivate(Request $request, EspacioAcademico $espacio, int $bloqueo, SpaceManagementService $service)
    {
        $service->deactivate($request->user(), $espacio, $bloqueo);

        return back()->with('success', 'Bloqueo desactivado. Su historial se conserva.');
    }

    public function availability(SpaceFilterRequest $request, SpaceAvailabilityService $service)
    {
        $filters = $request->validated();
        $period = null;
        $spaces = null;
        if (! empty($filters['fecha'])) {
            $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $filters['fecha'].' '.$filters['hora_inicio'], 'America/La_Paz');
            $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $filters['fecha'].' '.$filters['hora_fin'], 'America/La_Paz');
            $period = ['fecha' => $filters['fecha'], 'inicio' => $filters['hora_inicio'], 'fin' => $filters['hora_fin'], 'zona' => 'America/La_Paz'];
            $spaces = $service->candidates($filters, $start, $end)->orderBy('recurso_id')->paginate(15)->withQueryString();
        }

        return Inertia::render('Spaces/Availability', ['espacios' => $spaces, 'periodo' => $period, 'filtros' => $filters, 'opciones' => $this->options(), 'fechaActual' => now()->toDateString()]);
    }
}
