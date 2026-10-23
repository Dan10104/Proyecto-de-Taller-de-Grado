<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationActionRequest;
use App\Http\Requests\ReservationFilterRequest;
use App\Http\Requests\ReservationRequest;
use App\Models\EspacioAcademico;
use App\Models\Reserva;
use App\Services\AuthorizationService;
use App\Services\ReservationQueryService;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index(ReservationFilterRequest $request, ReservationQueryService $query, AuthorizationService $authorization)
    {
        $administrative = $request->administrative();
        $permissions = $authorization->permissions($request->user());
        $reservations = $query->filtered($request->user(), $administrative, $request->validated())->orderByDesc('solicitada_en')->orderByDesc('id')
            ->paginate(15)->withQueryString()->through(fn ($r) => $query->present($r, $request->user(), $permissions, $administrative));

        return Inertia::render('Reservations/Index', ['reservas' => $reservations, 'alcance' => $administrative ? 'administrativas' : 'propias',
            'filtros' => $request->validated(), 'opciones' => $query->options($request->user(), $administrative)]);
    }

    public function create(Request $request, ReservationQueryService $query)
    {
        Gate::authorize('create', Reserva::class);
        $data = $request->validate(array_intersect_key(ReservationRequest::inputRules(), array_flip(['espacio_id', 'inicio', 'fin'])));
        $space = EspacioAcademico::with(['recurso', 'piso', 'tipo'])->findOrFail($data['espacio_id']);

        return Inertia::render('Reservations/Create', ['espacio' => $query->space($space), 'periodo' => ['inicio' => $data['inicio'], 'fin' => $data['fin']]]);
    }

    public function store(ReservationRequest $request, ReservationService $service)
    {
        $reservation = $service->create($request->user(), $request->validated());

        return $this->confirmed($request, $reservation, 'Solicitud de reserva registrada. Queda pendiente de revisión.');
    }

    public function show(Request $request, Reserva $reserva, ReservationQueryService $query, AuthorizationService $authorization)
    {
        Gate::authorize('view', $reserva);
        $permissions = $authorization->permissions($request->user());
        $reserva->load(['espacio.recurso', 'espacio.piso', 'espacio.tipo', 'solicitante:id,nombres,apellidos', 'resueltaPor:id,nombres,apellidos', 'canceladaPor:id,nombres,apellidos']);

        return Inertia::render('Reservations/Show', ['reserva' => $query->present($reserva, $request->user(), $permissions, in_array('reservas.ver_administrativas', $permissions, true), true)]);
    }

    public function approve(ReservationActionRequest $request, Reserva $reserva, ReservationService $service)
    {
        $reservation = $service->approve($request->user(), $reserva);

        return $this->confirmed($request, $reservation, 'Reserva aprobada correctamente.');
    }

    public function reject(ReservationActionRequest $request, Reserva $reserva, ReservationService $service)
    {
        $reservation = $service->reject($request->user(), $reserva, $request->validated('motivo_rechazo'));

        return $this->confirmed($request, $reservation, 'Solicitud de reserva rechazada.');
    }

    public function cancel(ReservationActionRequest $request, Reserva $reserva, ReservationService $service)
    {
        $reservation = $service->cancel($request->user(), $reserva, $request->validated('motivo_cancelacion'));

        return $this->confirmed($request, $reservation, 'Reserva cancelada correctamente.');
    }

    private function confirmed(Request $request, Reserva $reservation, string $message)
    {
        return ($request->user()->can('view', $reservation) ? redirect()->route('reservas.show', $reservation) : redirect()->route('inicio'))->with('success', $message);
    }
}
