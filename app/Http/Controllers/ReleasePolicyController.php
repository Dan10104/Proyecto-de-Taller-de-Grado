<?php

namespace App\Http\Controllers;

use App\Models\EspacioAcademico;
use App\Models\PoliticaLiberacion;
use App\Services\ReleasePolicyManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReleasePolicyController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);

        return Inertia::render('Release/Policies', ['politicas' => PoliticaLiberacion::withCount('espacios')->when($filters['q'] ?? null, fn ($q, $text) => $q->where('nombre', 'like', '%'.$text.'%'))->orderBy('nombre')->orderByDesc('version')->paginate(15)->withQueryString(), 'filtros' => $filters]);
    }

    public function create(Request $request)
    {
        $data = $request->validate(['desde' => ['nullable', 'integer']]);
        $source = isset($data['desde']) ? PoliticaLiberacion::findOrFail($data['desde']) : null;

        return Inertia::render('Release/PolicyForm', ['origen' => $source]);
    }

    public function store(Request $request, ReleasePolicyManagementService $service)
    {
        $policy = $service->create($request->user(), $request->all());

        return redirect($request->user()->can('liberacion.politicas.ver') ? '/liberacion/politicas/'.$policy->id : '/inicio')->with('success', 'Versión creada. Sus reglas se conservarán y puede asignarla a un espacio sin compromisos vigentes.');
    }

    public function show(PoliticaLiberacion $politica)
    {
        return Inertia::render('Release/PolicyShow', ['politica' => $politica, 'espacios' => $politica->espacios()->with('recurso:id,codigo,nombre')->orderBy('recurso_id')->paginate(15)]);
    }

    public function state(Request $request, PoliticaLiberacion $politica, ReleasePolicyManagementService $service)
    {
        $data = $request->validate(['activa' => ['required', 'boolean']]);
        $service->state($request->user(), $politica->id, (bool) $data['activa']);

        return back()->with('success', 'Estado de la política actualizado. Las evaluaciones históricas se conservan.');
    }

    public function assignments(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $spaces = EspacioAcademico::with(['recurso:id,codigo,nombre', 'politica'])->when($filters['q'] ?? null, fn ($q, $text) => $q->whereHas('recurso', fn ($q) => $q->where('nombre', 'like', '%'.$text.'%')->orWhere('codigo', 'like', '%'.$text.'%')))->orderBy('recurso_id')->paginate(15)->withQueryString();

        return Inertia::render('Release/Assignments', ['espacios' => $spaces, 'politicas' => PoliticaLiberacion::where('activa', true)->orderBy('nombre')->orderByDesc('version')->get(), 'filtros' => $filters]);
    }

    public function assign(Request $request, EspacioAcademico $espacio, ReleasePolicyManagementService $service)
    {
        $data = $request->validate(['politica_id' => ['present', 'nullable', 'integer']]);
        $service->assign($request->user(), $espacio->recurso_id, $data['politica_id'] === null ? null : (int) $data['politica_id']);

        return back()->with('success', 'Asignación de política guardada.');
    }
}
