<?php

namespace Tests\Feature;

use App\Models\CaracteristicaEspacio;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\TipoEspacio;
use App\Services\SpaceAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SpaceTestCase;

class SpaceAvailabilityTest extends SpaceTestCase
{
    public static function roles(): array
    {
        return [['Administrador'], ['Encargado de Laboratorio'], ['Docente'], ['Estudiante']];
    }

    #[DataProvider('roles')]
    public function test_four_roles_can_consult_spaces_and_availability(string $role): void
    {
        $space = EspacioAcademico::factory()->create();
        $this->login($this->user($role));
        $this->get('/disponibilidad')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Spaces/Availability')->where('espacios', null)->where('periodo', null));
        $this->get($this->availabilityUrl())->assertOk()->assertInertia(fn (Assert $p) => $p->where('espacios.total', 1)->where('espacios.data.0.recurso_id', $space->getKey())->where('periodo.zona', 'America/La_Paz'));
        $this->get('/espacios')->assertOk();
        $this->get('/espacios/'.$space->getKey())->assertOk();
    }

    public function test_guest_and_user_without_roles_cannot_consult(): void
    {
        $this->get('/disponibilidad')->assertRedirect('/login');
        $this->get('/espacios')->assertRedirect('/login');
        $this->login($this->user());
        $this->get($this->availabilityUrl())->assertForbidden();
    }

    public static function invalidQueries(): array
    {
        return [[['hora_fin' => '08:00'], 'hora_fin'], [['hora_fin' => '07:59'], 'hora_fin'], [['fecha' => '2027-02-30'], 'fecha'], [['hora_inicio' => '25:00'], 'hora_inicio'], [['hora_fin' => ''], 'hora_fin'], [['capacidad_minima' => 0], 'capacidad_minima'], [['piso_id' => 999999], 'piso_id'], [['estado' => 'ACTIVO'], 'estado']];
    }

    #[DataProvider('invalidQueries')]
    public function test_invalid_filters_are_rejected(array $query, string $field): void
    {
        $this->login($this->user('Docente'));
        $this->get($this->availabilityUrl($query))->assertSessionHasErrors($field);
    }

    public static function intervals(): array
    {
        return [
            'before contiguous' => ['07:00', '08:00', true, 1], 'after contiguous' => ['09:00', '10:00', true, 1],
            'exact' => ['08:00', '09:00', true, 0], 'inside' => ['08:15', '08:45', true, 0], 'contains' => ['07:00', '10:00', true, 0],
            'overlap left' => ['07:30', '08:30', true, 0], 'overlap right' => ['08:30', '09:30', true, 0],
            'inactive' => ['08:00', '09:00', false, 1], 'outside' => ['10:00', '11:00', true, 1],
        ];
    }

    #[DataProvider('intervals')]
    public function test_half_open_intervals_and_active_blocks(string $start, string $end, bool $active, int $total): void
    {
        $space = EspacioAcademico::factory()->create();
        $actor = $this->user('Docente');
        $this->blockFor($space, '2027-01-10 '.$start, '2027-01-10 '.$end, $active, $actor);
        $this->login($actor);
        $this->get($this->availabilityUrl())->assertInertia(fn (Assert $p) => $p->where('espacios.total', $total));
    }

    public function test_state_filters_exclude_blocked_and_maintenance_candidates(): void
    {
        foreach (['DISPONIBLE', 'BLOQUEADO', 'EN_MANTENIMIENTO'] as $state) {
            EspacioAcademico::factory()->create(['estado' => $state]);
        }
        $this->login($this->user('Estudiante'));
        $this->get($this->availabilityUrl())->assertInertia(fn (Assert $p) => $p->where('espacios.total', 1)->where('espacios.data.0.estado', 'DISPONIBLE'));
        foreach (['BLOQUEADO', 'EN_MANTENIMIENTO'] as $state) {
            $this->get($this->availabilityUrl(['estado' => $state]))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 0));
            $this->get('/espacios?estado='.$state)->assertInertia(fn (Assert $p) => $p->where('espacios.total', 1));
        }
    }

    public function test_floor_type_capacity_search_and_all_features_filters(): void
    {
        $a = EspacioAcademico::factory()->create(['capacidad' => 40]);
        $b = EspacioAcademico::factory()->create(['capacidad' => 20]);
        $a->recurso->update(['codigo' => 'ESP-0-SINTETICO', 'nombre' => 'Sintético A']);
        $b->recurso->update(['codigo' => 'ESP-B-SINTETICO', 'nombre' => 'Sintético B']);
        $features = CaracteristicaEspacio::factory()->count(2)->create();
        $a->caracteristicas()->attach($features);
        $b->caracteristicas()->attach($features[0]);
        $this->login($this->user('Docente'));
        foreach ([['piso_id' => $a->piso_id], ['tipo_espacio_id' => $a->tipo_espacio_id], ['capacidad_minima' => 30], ['q' => $a->recurso->codigo], ['q' => '0'], ['caracteristicas' => $features->pluck('id')->all()]] as $filter) {
            $this->get($this->availabilityUrl($filter))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 1)->where('espacios.data.0.recurso_id', $a->getKey()));
        }
        $this->get($this->availabilityUrl(['caracteristicas' => [$features[0]->id]]))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 2));
        $this->get($this->availabilityUrl(['capacidad_minima' => 100]))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 0)->has('espacios.data', 0));
    }

    public function test_reader_detail_does_not_expose_block_reasons_or_responsible_person(): void
    {
        $space = EspacioAcademico::factory()->create();
        $this->blockFor($space, '2090-01-10 08:00', '2090-01-10 09:00');
        $this->login($this->user('Estudiante'));
        $this->get('/espacios/'.$space->getKey())->assertInertia(fn (Assert $p) => $p->has('bloqueos.data', 1)->missing('bloqueos.data.0.motivo')->missing('bloqueos.data.0.responsable')->missing('bloqueos.data.0.responsable_id'));
    }

    public function test_lists_are_paginated_and_query_count_does_not_grow_per_space(): void
    {
        $floor = Piso::factory()->create();
        $type = TipoEspacio::factory()->create();
        EspacioAcademico::factory()->count(18)->create(['piso_id' => $floor->id, 'tipo_espacio_id' => $type->id]);
        $this->login($this->user('Administrador'));
        $this->get('/espacios')->assertInertia(fn (Assert $p) => $p->has('espacios.data', 15)->where('espacios.total', 18)->where('espacios.per_page', 15));
        $this->get($this->availabilityUrl(['page' => 2]))->assertInertia(fn (Assert $p) => $p->has('espacios.data', 3)->where('espacios.total', 18));
        DB::enableQueryLog();
        DB::flushQueryLog();
        app(SpaceAvailabilityService::class)->candidates([], CarbonImmutable::parse('2027-01-10 08:00', 'America/La_Paz'), CarbonImmutable::parse('2027-01-10 09:00', 'America/La_Paz'))->get();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(5, $queries);
        $this->assertFalse(Schema::hasTable('reservas'));
        foreach ($queries as $query) {
            $this->assertStringNotContainsString('reservas', strtolower($query['query']));
        }
    }
}
