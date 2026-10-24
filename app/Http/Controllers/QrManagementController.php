<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\Prestamo;
use App\Models\RecursoInstitucional;
use App\Models\Reserva;
use App\Services\QrContextService;
use App\Services\QrManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class QrManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('qr.gestionar');
        $filters = $request->validate(['tipo' => 'nullable|in:recurso,reserva,prestamo', 'q' => 'nullable|string|max:120']);
        $type = $filters['tipo'] ?? 'recurso';
        $search = trim($filters['q'] ?? '');
        if ($type === 'recurso') {
            $query = RecursoInstitucional::query()->where(function ($q) use ($request) {
                $q->whereRaw('0=1');
                if ($request->user()->can('espacios.ver')) {
                    $q->orWhereHas('espacio');
                }
                if ($request->user()->can('equipamientos.ver')) {
                    $q->orWhereHas('equipamiento');
                }
            })->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('codigo', 'like', "%$search%")->orWhere('nombre', 'like', "%$search%")));
        } else {
            Gate::authorize($type === 'reserva' ? 'reservas.ver_administrativas' : 'prestamos.ver_administrativos');
            $query = ($type === 'reserva' ? Reserva::query() : Prestamo::query())->when($search !== '', fn ($q) => $q->where('codigo', 'like', "%$search%"));
        }

        return Inertia::render('Qr/Index', ['tipo' => $type, 'q' => $search, 'objetivos' => $query->orderByDesc('id')->paginate(15)->withQueryString()->through(fn ($target) => ['id' => $target->id, 'codigo' => $target->codigo, 'nombre' => $target->nombre ?? ($type === 'reserva' ? 'Reserva de espacio' : 'Préstamo de equipamiento')])]);
    }

    public function show(Request $request, string $objetivoTipo, int $objetivo, QrContextService $contexts)
    {
        $target = $contexts->target($request->user(), $objetivoTipo, $objetivo);

        return Inertia::render('Qr/Manage', ['objetivo' => ['tipo' => $objetivoTipo, 'id' => $objetivo,
            'codigo' => $target['operation']?->codigo ?? $target['resource']->recurso->codigo,
            'nombre' => $target['resource']->recurso->nombre],
            'codigos' => CodigoQr::where($target['field'], $objetivo)->orderByDesc('id')->paginate(15)->through(fn ($code) => [
                'id' => $code->id, 'activo' => $code->activo, 'generada_en' => $code->generada_en->toIso8601String(),
                'vence_en' => $code->vence_en?->toIso8601String(),
                'vigente' => $code->activo && ! now()->lt($code->generada_en) && (! $code->vence_en || now()->lt($code->vence_en))])]);
    }

    public function issue(Request $request, string $objetivoTipo, int $objetivo, QrManagementService $service)
    {
        Gate::authorize('qr.gestionar');
        $data = $request->validate(['vence_en' => 'nullable|string|max:16']);

        return response()->json($service->issue($request->user(), $objetivoTipo, $objetivo, $data['vence_en'] ?? null, hash('sha256', $request->session()->getId())), 201)->header('Cache-Control', 'no-store, private');
    }

    public function deactivate(Request $request, string $objetivoTipo, int $objetivo, int $codigo, QrManagementService $service)
    {
        $service->deactivate($request->user(), $objetivoTipo, $objetivo, $codigo, hash('sha256', $request->session()->getId()));

        return response()->json(['message' => 'Código desactivado. Historial conservado.']);
    }
}
