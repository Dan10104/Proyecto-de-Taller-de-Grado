<?php

namespace App\Http\Controllers;

use App\Enums\AccionCritica;
use App\Jobs\RegisterTraceabilityEvidence;
use App\Models\EvidenciaTrazabilidad;
use App\Models\Rol;
use App\Services\Traceability\EvidenceProcessor;
use App\Services\Traceability\ExternalReference;
use App\Services\Traceability\TraceabilityUnavailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class TraceabilityController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['estado' => ['nullable', Rule::in(['PENDIENTE', 'REGISTRADA'])], 'operacion' => ['nullable', Rule::in(array_column(AccionCritica::cases(), 'value'))],
            'resultado' => ['nullable', Rule::in(['VALIDO', 'INCONSISTENTE', 'PENDIENTE', 'NO_VERIFICABLE'])], 'desde' => ['nullable', 'date_format:Y-m-d'], 'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde']]);
        $rows = EvidenciaTrazabilidad::with('actividad:id,accion,fecha_hora,reserva_id,prestamo_id')
            ->when($filters['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($filters['resultado'] ?? null, fn ($q, $v) => $q->where('resultado_verificacion', $v))
            ->when($filters['operacion'] ?? null, fn ($q, $v) => $q->whereHas('actividad', fn ($a) => $a->where('accion', $v)))
            ->when($filters['desde'] ?? null, fn ($q, $v) => $q->where('registrada_localmente_en', '>=', $v.' 00:00:00'))
            ->when($filters['hasta'] ?? null, fn ($q, $v) => $q->where('registrada_localmente_en', '<=', $v.' 23:59:59'))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Traceability/Index', ['evidencias' => $rows, 'filtros' => $filters]);
    }

    public function show(EvidenciaTrazabilidad $evidencia)
    {
        $evidencia->load('actividad:id,accion,fecha_hora,reserva_id,prestamo_id,recurso_id');

        return Inertia::render('Traceability/Show', ['evidencia' => $evidencia, 'explorador' => ExternalReference::explorer($evidencia->referencia_externa)]);
    }

    public function verify(Request $request, EvidenciaTrazabilidad $evidencia, EvidenceProcessor $processor)
    {
        try {
            $processor->verify($evidencia->id, $request->user()->id);
        } catch (TraceabilityUnavailable) {
            return back()->withErrors(['trazabilidad' => 'Otra comprobación está en curso. Intente nuevamente.']);
        }

        return back()->with('success', 'Verificación terminada. Consulte el resultado y la fecha de comprobación.');
    }

    public function retry(Request $request, EvidenciaTrazabilidad $evidencia)
    {
        DB::transaction(function () use ($request, $evidencia) {
            Rol::where('nombre', 'Administrador')->lockForUpdate()->firstOrFail();
            Gate::forUser($request->user()->fresh())->authorize('trazabilidad.evidencias.ver');
            Gate::forUser($request->user()->fresh())->authorize('trazabilidad.reintentar');
            abort_unless($evidencia->fresh()->estado->value === 'PENDIENTE', 409, 'La evidencia ya está registrada.');
            DB::afterCommit(function () use ($evidencia) {
                Cache::forget('traceability:retry:'.$evidencia->id);
                RegisterTraceabilityEvidence::dispatch($evidencia->id)->onConnection('deferred');
            });
        });

        return back()->with('success', 'Reintento solicitado. La evidencia conserva su identidad; actualice el detalle para consultar el resultado.');
    }
}
