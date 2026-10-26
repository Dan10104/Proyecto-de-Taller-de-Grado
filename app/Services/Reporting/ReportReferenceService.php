<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use App\Services\LoanQueryService;
use App\Services\ReservationQueryService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportReferenceService
{
    public function query(ReportDefinition $d, Usuario $actor, string $field): Builder
    {
        app(ReportRegistry::class)->authorize($d, ReportingAccess::for($actor));
        if (($d->filters[$field] ?? null) !== 'reference') {
            throw ValidationException::withMessages(['campo' => 'Filtro no permitido.']);
        }
        $resource = fn () => DB::table('recursos_institucionales as r')->select('r.id as option_id')->selectRaw("CONCAT(r.codigo, ' · ', r.nombre) as option_label");
        $user = fn () => DB::table('usuarios as u')->select('u.id as option_id')->selectRaw("CONCAT(u.nombres, ' ', u.apellidos) as option_label");
        if ($d->id === 'reservas') {
            $scope = app(ReservationQueryService::class)->scoped($actor, true);
            $q = match ($field) {
                'solicitante_id' => $user()->whereIn('u.id', (clone $scope)->select('solicitante_id')),
                'espacio_id' => $resource()->whereIn('r.id', (clone $scope)->select('espacio_id')),
                'piso_id' => DB::table('pisos')->select('id as option_id', 'nombre as option_label')->whereIn('id', DB::table('espacios_academicos')->select('piso_id')->whereIn('recurso_id', (clone $scope)->select('espacio_id'))),
            };
        } elseif ($d->id === 'prestamos') {
            $scope = app(LoanQueryService::class)->scoped($actor, true);
            $q = $field === 'solicitante_id' ? $user()->whereIn('u.id', (clone $scope)->select('solicitante_id')) : $resource()->whereIn('r.id', (clone $scope)->select('equipamiento_id'));
        } elseif ($d->id === 'espacios') {
            $q = $field === 'piso_id' ? DB::table('pisos')->select('id as option_id', 'nombre as option_label')->whereIn('id', DB::table('espacios_academicos')->select('piso_id')) : DB::table('tipos_espacio')->select('id as option_id', 'nombre as option_label')->whereIn('id', DB::table('espacios_academicos')->select('tipo_espacio_id'));
        } elseif ($d->id === 'equipamiento') {
            $q = $field === 'espacio_base_id' ? $resource()->whereIn('r.id', DB::table('equipamientos_tecnologicos')->select('espacio_base_id')) : DB::table('tipos_equipamiento')->select('id as option_id', 'nombre as option_label')->whereIn('id', DB::table('equipamientos_tecnologicos')->select('tipo_equipamiento_id'));
        } elseif ($d->id === 'incidencias') {
            $q = $resource()->whereIn('r.id', DB::table('incidencias_mantenimiento')->select('recurso_id'));
        } elseif ($d->id === 'iot') {
            $q = $field === 'sensor_id' ? DB::table('sensores_iot')->select('id as option_id', 'identificador as option_label')->whereIn('id', DB::table('eventos_ocupacion')->select('sensor_id')) : $resource()->whereIn('r.id', DB::table('eventos_ocupacion')->select('espacio_historico_id'));
        } else {
            throw ValidationException::withMessages(['campo' => 'Filtro no permitido.']);
        }

        return DB::query()->fromSub($q, 'reference_options');
    }

    public function search(ReportDefinition $d, Usuario $actor, string $field, string $search = '', ?int $selected = null): array
    {
        return $this->query($d, $actor, $field)->when($selected !== null, fn ($q) => $q->where('option_id', $selected), fn ($q) => $q->when($search !== '', fn ($q) => $q->where('option_label', 'like', '%'.addcslashes($search, '%_\\').'%')))->orderBy('option_label')->orderBy('option_id')->limit(30)->get()->map(fn ($r) => ['id' => (int) $r->option_id, 'nombre' => $r->option_label])->all();
    }
}
