<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AdministrationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'estado' => ['nullable', Rule::in(['ACTIVO', 'INACTIVO'])], 'rol' => ['nullable', 'integer', Rule::exists('roles', 'id')]]);
        $users = Usuario::query()->select('id', 'nombres', 'apellidos', 'correo', 'estado', 'fecha_registro')->with('rolesActivos:id,nombre');
        if ($q = $filters['q'] ?? null) {
            $users->where(fn ($query) => $query->where('nombres', 'like', "%$q%")->orWhere('apellidos', 'like', "%$q%")->orWhere('correo', 'like', "%$q%"));
        }
        if ($state = $filters['estado'] ?? null) {
            $users->where('estado', $state);
        }
        if ($role = $filters['rol'] ?? null) {
            $users->whereHas('rolesActivos', fn ($query) => $query->where('roles.id', $role));
        }

        return Inertia::render('Users/Index', ['usuarios' => $users->orderBy('apellidos')->orderBy('id')->paginate(15)->withQueryString(), 'filtros' => $filters, 'roles' => Rol::select('id', 'nombre')->orderBy('nombre')->get()]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Users/Form', ['usuario' => null, 'roles' => $request->user()->can('roles.asignar') ? Rol::select('id', 'nombre')->get() : []]);
    }

    public function store(UserRequest $request, AdministrationService $service)
    {
        $user = $service->createUser($request->user(), $request->validated());

        return redirect()->route($request->user()->can('usuarios.ver') ? 'usuarios.index' : 'inicio')->with('success', 'Usuario registrado correctamente.');
    }

    public function show(Request $request, Usuario $usuario)
    {
        $usuario->load('rolesActivos:id,nombre');

        return Inertia::render('Users/Show', ['usuario' => $usuario, 'roles' => $request->user()->can('roles.asignar') ? Rol::select('id', 'nombre')->get() : [],
            'historial' => $request->user()->can('roles.ver') ? $usuario->asignaciones()->with(['rol:id,nombre', 'asignador:id,nombres,apellidos', 'retirador:id,nombres,apellidos'])->orderByDesc('id')->paginate(15)->withQueryString() : null]);
    }

    public function edit(Usuario $usuario)
    {
        return Inertia::render('Users/Form', ['usuario' => $usuario->only(['id', 'nombres', 'apellidos', 'correo', 'estado']), 'roles' => []]);
    }

    public function update(UserRequest $request, Usuario $usuario, AdministrationService $service)
    {
        $service->updateUser($request->user(), $usuario, $request->validated());

        return redirect()->route($request->user()->can('usuarios.ver') ? 'usuarios.index' : 'inicio')->with('success', 'Usuario actualizado. Si cambió la contraseña, sus sesiones quedaron revocadas.');
    }

    public function disable(Request $request, Usuario $usuario, AdministrationService $service)
    {
        $service->disableUser($request->user(), $usuario);

        return back()->with('success', 'Usuario deshabilitado; sus sesiones quedaron revocadas.');
    }
}
