<?php

namespace App\Http\Controllers;

use App\Models\CaracteristicaEspacio;
use App\Models\Piso;
use App\Models\TipoEspacio;
use App\Services\SpaceManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SpaceCatalogController extends Controller
{
    private const MODELS = ['pisos' => Piso::class, 'tipos' => TipoEspacio::class, 'caracteristicas' => CaracteristicaEspacio::class];

    public function index(Request $request, string $catalogo)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = self::MODELS[$catalogo]::query();
        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $query->where('nombre', 'like', "%$q%");
        }

        return Inertia::render('Spaces/Catalogs', ['catalogo' => $catalogo, 'registros' => $query->orderBy($catalogo === 'pisos' ? 'numero' : 'nombre')->paginate(15)->withQueryString(), 'filtros' => $filters]);
    }

    public function save(Request $request, string $catalogo, SpaceManagementService $service, ?int $registro = null)
    {
        $class = self::MODELS[$catalogo];
        $record = $registro ? $class::findOrFail($registro) : new $class;
        $uniqueField = $catalogo === 'pisos' ? 'numero' : ($catalogo === 'tipos' ? 'codigo' : 'nombre');
        $rules = ['nombre' => ['required', 'string', 'max:'.($catalogo === 'pisos' ? 100 : 120)]];
        if ($catalogo === 'pisos') {
            $rules['numero'] = ['required', 'integer', 'min:-2147483648', 'max:2147483647'];
        }
        if ($catalogo === 'tipos') {
            $rules['codigo'] = ['required', 'string', 'max:60'];
        }
        if ($catalogo === 'caracteristicas') {
            $rules['descripcion'] = ['nullable', 'string', 'max:255'];
        }
        $rules[$uniqueField][] = Rule::unique($record->getTable(), $uniqueField)->ignore($record->getKey());
        $data = $request->validate($rules);
        $service->saveCatalog($request->user(), $catalogo, $registro, $data);

        return redirect()->route('espacios.catalogos', $catalogo)->with('success', 'Catálogo actualizado correctamente.');
    }
}
