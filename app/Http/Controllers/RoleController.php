<?php

namespace App\Http\Controllers;

use App\Enums\RolInstitucional;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\PermissionRequest;
use App\Http\Requests\RoleRequest;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AdministrationService;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index', ['roles' => Rol::withCount(['permisos', 'usuariosActivos'])->orderBy('id')->get(), 'faltantes' => array_values(array_diff(RolInstitucional::nombres(), Rol::pluck('nombre')->all()))]);
    }

    public function create()
    {
        return Inertia::render('Roles/Form', ['rol' => null, 'nombres' => array_values(array_diff(RolInstitucional::nombres(), Rol::pluck('nombre')->all()))]);
    }

    public function store(RoleRequest $request, AdministrationService $service)
    {
        $role = $service->saveRole($request->user(), null, $request->validated());

        return redirect()->route($request->user()->can('roles.ver') ? 'roles.index' : 'inicio')->with('success', 'Rol institucional registrado.');
    }

    public function edit(Rol $rol)
    {
        return Inertia::render('Roles/Form', ['rol' => $rol, 'nombres' => [$rol->nombre]]);
    }

    public function update(RoleRequest $request, Rol $rol, AdministrationService $service)
    {
        $service->saveRole($request->user(), $rol, $request->validated());

        return redirect()->route($request->user()->can('roles.ver') ? 'roles.index' : 'inicio')->with('success', 'Rol actualizado.');
    }

    public function show(Request $request, Rol $rol)
    {
        return Inertia::render('Roles/Show', ['rol' => $rol->load('permisos'), 'catalogo' => $request->user()->can('permisos.ver') ? Permiso::whereIn('codigo', PermissionCatalog::forRole($rol->nombre))->orderBy('codigo')->get() : [],
            'usuarios' => $rol->usuariosActivos()->select('usuarios.id', 'nombres', 'apellidos', 'correo', 'estado')->orderBy('usuarios.id')->paginate(15)->withQueryString()]);
    }

    public function permissions(PermissionRequest $request, Rol $rol, AdministrationService $service)
    {
        $service->setPermissions($request->user(), $rol, $request->validated('permisos'));

        return back()->with('success', 'Permisos actualizados. Se aplican desde la siguiente solicitud.');
    }

    public function catalog()
    {
        return Inertia::render('Roles/Permissions', ['permisos' => Permiso::orderBy('codigo')->get()]);
    }

    public function candidates(Request $request)
    {
        $q = $request->validate(['q' => ['required', 'string', 'min:2', 'max:120']])['q'];

        return Usuario::select('id', 'nombres', 'apellidos', 'correo', 'estado')->where(fn ($query) => $query->where('nombres', 'like', "%$q%")->orWhere('apellidos', 'like', "%$q%")->orWhere('correo', 'like', "%$q%"))->orderBy('id')->limit(20)->get();
    }

    public function assign(AssignmentRequest $request, Rol $rol, AdministrationService $service)
    {
        $service->assign($request->user(), Usuario::findOrFail($request->validated('usuario_id')), $rol);

        return back()->with('success', 'Rol asignado correctamente.');
    }

    public function withdraw(AssignmentRequest $request, Rol $rol, AdministrationService $service)
    {
        $service->withdraw($request->user(), Usuario::findOrFail($request->validated('usuario_id')), $rol);

        return back()->with('success', 'Rol retirado. El historial se conserva.');
    }
}
