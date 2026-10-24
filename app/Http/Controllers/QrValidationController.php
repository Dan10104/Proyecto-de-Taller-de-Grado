<?php

namespace App\Http\Controllers;

use App\Enums\ResultadoValidacionQr;
use App\Enums\TipoOperacionQr;
use App\Http\Requests\QrValidationRequest;
use App\Models\CodigoQr;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\ValidacionQr;
use App\Services\QrValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class QrValidationController extends Controller
{
    public function show(Request $request, Reserva $reserva)
    {
        Gate::authorize('useSpace', [CodigoQr::class, $reserva]);

        return Inertia::render('Qr/Space', ['reserva' => ['id' => $reserva->id, 'codigo' => $reserva->codigo,
            'espacio' => $reserva->espacio->recurso->nombre, 'estado' => $reserva->estado->value,
            'inicio' => $reserva->inicio->toIso8601String(), 'fin' => $reserva->fin->toIso8601String()],
            'validaciones' => $this->history('reserva_id', $reserva->id)]);
    }

    public function loanHistory(Request $request, Prestamo $prestamo)
    {
        Gate::authorize('prestamos.ver_administrativos');
        Gate::authorize('qr.validar_operacion');

        return response()->json(['validaciones' => $this->history('prestamo_id', $prestamo->id)]);
    }

    public function useSpace(QrValidationRequest $request, Reserva $reserva, QrValidationService $service)
    {
        return $this->result($service->useSpace($request->user(), $reserva, $request->validated('qr_token'), hash('sha256', $request->session()->getId())));
    }

    public function deliver(QrValidationRequest $request, Prestamo $prestamo, QrValidationService $service)
    {
        return $this->operation($request, $prestamo, $service, TipoOperacionQr::ENTREGA);
    }

    public function receive(QrValidationRequest $request, Prestamo $prestamo, QrValidationService $service)
    {
        return $this->operation($request, $prestamo, $service, TipoOperacionQr::DEVOLUCION);
    }

    private function operation(QrValidationRequest $request, Prestamo $loan, QrValidationService $service, TipoOperacionQr $type)
    {
        return $this->result($service->operation($request->user(), $loan, $type, $request->validated('qr_token'), $request->safe()->except('qr_token'), hash('sha256', $request->session()->getId())));
    }

    private function result(ValidacionQr $validation)
    {
        return response()->json(['validacion' => $this->present($validation), 'message' => $validation->motivo_rechazo ?? 'Validación correcta. Operación registrada.'], $validation->resultado === ResultadoValidacionQr::VALIDA ? 200 : 409);
    }

    private function history(string $field, int $id)
    {
        return ValidacionQr::where($field, $id)->orderByDesc('id')->paginate(15)->through(fn ($v) => $this->present($v));
    }

    private function present(ValidacionQr $v): array
    {
        return ['id' => $v->id, 'resultado' => $v->resultado->value, 'tipo_operacion' => $v->tipo_operacion->value,
            'fecha_hora' => $v->fecha_hora->toIso8601String(), 'motivo_rechazo' => $v->motivo_rechazo,
            'entrega_id' => $v->entrega_id, 'devolucion_id' => $v->devolucion_id];
    }
}
