<?php

namespace App\Http\Controllers;

use App\Enums\TipoNotificacion;
use App\Models\Notificacion;
use App\Services\Notifications\NotificationLink;
use App\Services\Notifications\NotificationReadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['lectura' => ['nullable', Rule::in(['no_leidas', 'leidas'])], 'tipo' => ['nullable', Rule::enum(TipoNotificacion::class)], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = Notificacion::where('destinatario_id', $request->user()->id);
        if (($filters['lectura'] ?? null) === 'no_leidas') {
            $query->whereNull('leida_en');
        } elseif (($filters['lectura'] ?? null) === 'leidas') {
            $query->whereNotNull('leida_en');
        }
        if ($filters['tipo'] ?? null) {
            $query->where('tipo', $filters['tipo']);
        }

        return Inertia::render('Notifications/Index', ['notificaciones' => $query->orderByDesc('creada_en')->orderByDesc('id')->paginate(15, ['id', 'titulo', 'tipo', 'creada_en', 'leida_en'])->withQueryString()->through(fn ($n) => $this->summary($n)), 'filtros' => $filters]);
    }

    public function show(Request $request, int $notificacion, NotificationLink $links)
    {
        $n = Notificacion::where('destinatario_id', $request->user()->id)->findOrFail($notificacion);

        return Inertia::render('Notifications/Show', ['notificacion' => $this->summary($n) + ['mensaje' => $n->mensaje], 'enlace' => $links->resolve($n, $request->user())]);
    }

    public function read(Request $request, int $notificacion, NotificationReadService $reader)
    {
        $n = $reader->mark($request->user(), $notificacion);

        return response()->json(['leida_en' => $n->leida_en->toIso8601String(), 'no_leidas' => $reader->unread($request->user())]);
    }

    private function summary(Notificacion $n): array
    {
        return ['id' => $n->id, 'titulo' => $n->titulo, 'tipo' => $n->tipo->value, 'creada_en' => $n->creada_en->toIso8601String(), 'leida_en' => $n->leida_en?->toIso8601String()];
    }
}
