<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentFilterRequest;
use App\Http\Requests\IncidentRequest;
use App\Models\IncidenciaMantenimiento;
use App\Models\Prestamo;
use App\Models\RecursoInstitucional;
use App\Models\Reserva;
use App\Services\IncidentMaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class IncidentController extends Controller
{
    public function index(IncidentFilterRequest $r)
    {
        $q = IncidenciaMantenimiento::with('recurso');
        foreach (['estado', 'tipo', 'recurso_id'] as $field) {
            if ($r->filled($field)) {
                $q->where($field, $r->validated($field));
            }
        }
        if ($r->filled('q')) {
            $text = $r->validated('q');
            $q->whereHas('recurso', fn ($q) => $q->where(fn ($q) => $q->where('nombre', 'like', "%$text%")->orWhere('codigo', 'like', "%$text%")));
        }

        return Inertia::render('Incidents/Index', ['incidencias' => $q->orderByDesc('inicio')->orderByDesc('id')->paginate(15)->withQueryString()->through(fn ($i) => ['id' => $i->id, 'recurso' => $i->recurso->only(['codigo', 'nombre']), 'tipo' => $i->tipo->value, 'estado' => $i->estado->value, 'descripcion' => $i->descripcion, 'inicio' => $i->inicio->format('Y-m-d H:i'), 'fin_previsto' => $i->fin_previsto?->format('Y-m-d H:i')]), 'filtros' => $r->validated()]);
    }

    public function create(Request $r)
    {
        Gate::authorize('create', IncidenciaMantenimiento::class);

        return Inertia::render('Incidents/Create', ['recursos' => RecursoInstitucional::where(fn ($q) => $q->whereHas('equipamiento')->orWhereHas('espacio'))->withExists('equipamiento')->orderBy('nombre')->get(['id', 'codigo', 'nombre']), 'seleccion' => $r->validate(['recurso_id' => 'nullable|integer|exists:recursos_institucionales,id']), 'ahora' => now()->format('Y-m-d\TH:i')]);
    }

    public function store(IncidentRequest $r, IncidentMaintenanceService $s)
    {
        $i = $s->open($r->user(), $r->validated());

        return redirect($r->user()->can('view', $i) ? '/incidencias/'.$i->id : '/inicio')->with('success', 'Restricción registrada. Revise los compromisos afectados; sus estados se conservan.');
    }

    public function show(Request $r, IncidenciaMantenimiento $incidencia)
    {
        Gate::authorize('view', $incidencia);
        $incidencia->load(['recurso.equipamiento', 'recurso.espacio', 'reportadoPor:id,nombres,apellidos', 'cerradoPor:id,nombres,apellidos', 'devolucion.prestamo']);
        $equipment = $incidencia->recurso->equipamiento;
        $loanQuery = Prestamo::where('equipamiento_id', $incidencia->recurso_id)->where(fn ($q) => $q->where('estado', 'ENTREGADO')->orWhere(fn ($q) => $q->where('estado', 'APROBADO')->where('fin_previsto', '>', now())));
        $reservationQuery = Reserva::where('espacio_id', $incidencia->recurso_id)->where('estado', 'APROBADA')->where('fin', '>', now());
        $loans = $r->user()->can('prestamos.ver_administrativos') ? $loanQuery->orderBy('id')->paginate(10, ['id', 'codigo', 'estado', 'inicio_previsto', 'fin_previsto'], 'prestamos_page')->withQueryString()->through(fn ($p) => ['id' => $p->id, 'codigo' => $p->codigo, 'estado' => $p->estado->value, 'inicio_previsto' => $p->inicio_previsto->format('Y-m-d H:i'), 'fin_previsto' => $p->fin_previsto->format('Y-m-d H:i')]) : null;
        $reservations = $r->user()->can('reservas.ver_administrativas') ? $reservationQuery->orderBy('id')->paginate(10, ['id', 'codigo', 'estado', 'inicio', 'fin'], 'reservas_page')->withQueryString()->through(fn ($r) => ['id' => $r->id, 'codigo' => $r->codigo, 'estado' => $r->estado->value, 'inicio' => $r->inicio->format('Y-m-d H:i'), 'fin' => $r->fin->format('Y-m-d H:i')]) : null;

        return Inertia::render('Incidents/Show', ['incidencia' => [...$incidencia->only(['id', 'tipo', 'estado', 'descripcion', 'solucion']),
            'recurso' => [...$incidencia->recurso->only(['id', 'codigo', 'nombre']), 'equipamiento' => (bool) $equipment, 'estado' => ($equipment ?? $incidencia->recurso->espacio)->estado->value],
            'inicio' => $incidencia->inicio->format('Y-m-d H:i'), 'fin_previsto' => $incidencia->fin_previsto?->format('Y-m-d H:i'), 'cerrada_en' => $incidencia->cerrada_en?->format('Y-m-d H:i:s'),
            'reportado_por' => $incidencia->reportadoPor->only(['nombres', 'apellidos']), 'cerrado_por' => $incidencia->cerradoPor?->only(['nombres', 'apellidos']),
            'prestamo_asociado' => $r->user()->can('prestamos.ver_administrativos') ? $incidencia->devolucion?->prestamo?->only(['id', 'codigo']) : null],
            'prestamos' => $loans, 'reservas' => $reservations, 'compromisos' => $equipment ? $loanQuery->count() : $reservationQuery->count()]);
    }

    public function close(IncidentRequest $r, IncidenciaMantenimiento $incidencia, IncidentMaintenanceService $s)
    {
        $s->close($r->user(), $incidencia, $r->validated());

        return redirect($r->user()->can('view', $incidencia) ? '/incidencias/'.$incidencia->id : '/inicio')->with('success', 'Cierre registrado. La recuperación depende de la aptitud y de las restricciones restantes.');
    }
}
