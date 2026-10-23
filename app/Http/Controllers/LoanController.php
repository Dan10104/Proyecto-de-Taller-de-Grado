<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanActionRequest;
use App\Http\Requests\LoanFilterRequest;
use App\Http\Requests\LoanRequest;
use App\Models\EquipamientoTecnologico;
use App\Models\Prestamo;
use App\Services\AuthorizationService;
use App\Services\LoanQueryService;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index(LoanFilterRequest $request, LoanQueryService $query, AuthorizationService $auth)
    {
        $admin = $request->administrative();
        $permissions = $auth->permissions($request->user());

        return Inertia::render('Loans/Index', ['prestamos' => $query->filtered($request->user(), $admin, $request->validated())->orderByDesc('solicitado_en')->orderByDesc('id')->paginate(15)->withQueryString()->through(fn ($p) => $query->present($p, $admin, $permissions)), 'alcance' => $admin ? 'administrativos' : 'propios', 'filtros' => $request->validated(), 'opciones' => $query->options($request->user(), $admin)]);
    }

    public function create(Request $request, LoanQueryService $query)
    {
        Gate::authorize('create', Prestamo::class);
        $data = $request->validate(array_intersect_key(LoanRequest::inputRules(), array_flip(['equipamiento_id', 'inicio_previsto', 'fin_previsto'])));
        $equipment = EquipamientoTecnologico::with(['recurso', 'tipo', 'espacioBase.recurso', 'espacioBase.piso'])->findOrFail($data['equipamiento_id']);

        return Inertia::render('Loans/Create', ['equipamiento' => $query->equipment($equipment), 'seleccion' => $data]);
    }

    public function store(LoanRequest $request, LoanService $service)
    {
        return $this->redirect($request, $service->create($request->user(), $request->validated()), 'Solicitud enviada para revisión. Aún no compromete disponibilidad.');
    }

    public function show(Request $request, Prestamo $prestamo, LoanQueryService $query, AuthorizationService $auth)
    {
        Gate::authorize('view', $prestamo);
        $prestamo->load(['equipamiento.recurso', 'equipamiento.tipo', 'equipamiento.espacioBase.recurso', 'equipamiento.espacioBase.piso', 'solicitante:id,nombres,apellidos', 'resueltoPor:id,nombres,apellidos', 'entrega.entregadoPor:id,nombres,apellidos', 'entrega.receptor:id,nombres,apellidos', 'devolucion.recibidoPor:id,nombres,apellidos', 'devolucion.confirmadoPor:id,nombres,apellidos']);

        return Inertia::render('Loans/Show', ['prestamo' => $query->present($prestamo, $request->user()->can('prestamos.ver_administrativos'), $auth->permissions($request->user()), true)]);
    }

    private function redirect(Request $r, Prestamo $p, string $message)
    {
        return redirect($r->user()->can('view', $p) ? '/prestamos/'.$p->id : '/inicio')->with('success', $message);
    }

    public function approve(LoanActionRequest $r, Prestamo $prestamo, LoanService $s)
    {
        return $this->redirect($r, $s->approve($r->user(), $prestamo), 'Préstamo aprobado.');
    }

    public function reject(LoanActionRequest $r, Prestamo $prestamo, LoanService $s)
    {
        return $this->redirect($r, $s->reject($r->user(), $prestamo, $r->validated('motivo_rechazo')), 'Solicitud rechazada con motivo registrado.');
    }

    public function deliver(LoanActionRequest $r, Prestamo $prestamo, LoanService $s)
    {
        return $this->redirect($r, $s->deliver($r->user(), $prestamo, $r->validated()), 'Entrega registrada al solicitante.');
    }

    public function receive(LoanActionRequest $r, Prestamo $prestamo, LoanService $s)
    {
        return $this->redirect($r, $s->receive($r->user(), $prestamo, $r->validated()), 'Devolución registrada. Consulte la condición y el estado operativo resultante.');
    }

    public function review(LoanActionRequest $r, Prestamo $prestamo, LoanService $s)
    {
        return $this->redirect($r,$s->confirmReview($r->user(),$prestamo,$r->validated()),'Revisión de devolución confirmada.');
    }
}
