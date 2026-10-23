<?php

namespace Tests\Feature;

use App\Models\CaracteristicaEspacio;
use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\RecursoInstitucional;
use App\Models\Rol;
use Database\Seeders\SecuritySeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SpaceTestCase;

class SpaceManagementTest extends SpaceTestCase
{
    public static function managers(): array
    {
        return [['Administrador'], ['Encargado de Laboratorio']];
    }

    #[DataProvider('managers')]
    public function test_authorized_manager_can_create_edit_replace_features_and_change_state(string $role): void
    {
        $actor = $this->user($role);
        $this->login($actor);
        $features = CaracteristicaEspacio::factory()->count(2)->create();
        $data = $this->spaceData(['caracteristicas' => $features->pluck('id')->all()]);
        $this->get('/espacios/crear')->assertOk();
        $this->post('/espacios', $data)->assertRedirect();
        $space = EspacioAcademico::sole();
        $this->assertSame($data['codigo'], $space->recurso->codigo);
        $this->assertSame($space->recurso->id, $space->getKey());
        $this->assertSame(2, $space->caracteristicas()->count());
        $this->get('/espacios/'.$space->getKey().'/editar')->assertOk();
        $data['nombre'] = 'Espacio sintético editado';
        $data['capacidad'] = 45;
        $data['caracteristicas'] = [$features[1]->id];
        $this->put('/espacios/'.$space->getKey(), $data)->assertRedirect();
        $space->refresh();
        $this->assertSame(45, $space->capacidad);
        $this->assertSame($data['nombre'], $space->recurso->nombre);
        $this->assertSame([$features[1]->id], $space->caracteristicas()->pluck('caracteristicas_espacio.id')->all());
        $this->patch('/espacios/'.$space->getKey().'/estado', ['estado' => 'EN_MANTENIMIENTO'])->assertRedirect();
        $this->assertDatabaseHas('espacios_academicos', ['recurso_id' => $space->getKey(), 'estado' => 'EN_MANTENIMIENTO']);
        $this->patch('/espacios/'.$space->getKey().'/estado', ['estado' => 'OCUPADO'])->assertSessionHasErrors('estado');
        $this->assertDatabaseCount('recursos_institucionales', 1);
    }

    public static function readers(): array
    {
        return [['Docente'], ['Estudiante']];
    }

    #[DataProvider('readers')]
    public function test_readers_cannot_mutate_spaces_or_catalogs_even_by_direct_request(string $role): void
    {
        $space = EspacioAcademico::factory()->create();
        $actor = $this->user($role);
        $this->login($actor);
        $url = '/espacios/'.$space->getKey();
        $this->get('/espacios/crear')->assertForbidden();
        $this->get($url.'/editar')->assertForbidden();
        $this->get('/espacios/catalogos/pisos')->assertForbidden();
        $this->post('/espacios', $this->spaceData())->assertForbidden();
        $this->put($url, $this->spaceData())->assertForbidden();
        $this->patch($url.'/estado', ['estado' => 'BLOQUEADO'])->assertForbidden();
        $this->post($url.'/bloqueos', [])->assertForbidden();
        $this->patch($url.'/bloqueos/1/desactivar')->assertForbidden();
        $this->post('/espacios/catalogos/tipos', ['codigo' => 'TEST', 'nombre' => 'Sintético'])->assertForbidden();
        $this->assertDatabaseCount('espacios_academicos', 1);
        $this->assertDatabaseCount('bloqueos_espacio', 0);
        $this->assertSame('DISPONIBLE', $space->fresh()->estado->value);
    }

    public static function invalidData(): array
    {
        return [['capacidad', 0], ['capacidad', -1], ['capacidad', 1.5], ['codigo', ''], ['nombre', ''], ['piso_id', 999999], ['tipo_espacio_id', 999999], ['estado', 'OCUPADO'], ['caracteristicas', [999999]], ['politica_liberacion_id', 1], ['recurso_id', 1]];
    }

    #[DataProvider('invalidData')]
    public function test_invalid_space_data_is_rejected_without_partial_writes(string $field, mixed $value): void
    {
        $this->login($this->user('Administrador'));
        $this->post('/espacios', $this->spaceData([$field => $value]))->assertSessionHasErrors($field === 'caracteristicas' ? 'caracteristicas.0' : $field);
        $this->assertDatabaseCount('recursos_institucionales', 0);
        $this->assertDatabaseCount('espacios_academicos', 0);
    }

    public function test_global_resource_code_uniqueness_includes_resources_without_space_subclass(): void
    {
        RecursoInstitucional::factory()->create(['codigo' => 'RECURSO-GLOBAL']);
        $this->login($this->user('Administrador'));
        $this->post('/espacios', $this->spaceData(['codigo' => 'recurso-global']))->assertSessionHasErrors('codigo');
        $this->assertDatabaseCount('recursos_institucionales', 1);
        $this->assertDatabaseCount('espacios_academicos', 0);
    }

    public function test_duplicate_features_are_rejected_and_no_delete_route_is_exposed(): void
    {
        $feature = CaracteristicaEspacio::factory()->create();
        $space = EspacioAcademico::factory()->create();
        $this->login($this->user('Administrador'));
        $this->post('/espacios', $this->spaceData(['caracteristicas' => [$feature->id, $feature->id]]))->assertSessionHasErrors('caracteristicas.0');
        $this->delete('/espacios/'.$space->getKey())->assertStatus(405);
        $this->get('/espacios/999999/editar')->assertNotFound();
        $this->assertDatabaseCount('espacios_academicos', 1);
    }

    public function test_state_and_block_permissions_are_independent_of_data_management(): void
    {
        $actor = $this->user('Encargado de Laboratorio');
        $role = $actor->rolesActivos()->sole();
        $role->permisos()->detach(Permiso::whereIn('codigo', ['espacios.cambiar_estado', 'espacios.gestionar_bloqueos'])->pluck('id'));
        $this->login($actor);
        $data = $this->spaceData();
        $this->post('/espacios', $data)->assertRedirect();
        $space = EspacioAcademico::sole();
        $url = '/espacios/'.$space->getKey();
        $this->put($url, [...$data, 'estado' => 'BLOQUEADO'])->assertForbidden();
        $this->post('/espacios', [...$data, 'codigo' => 'OTRO-TEST', 'estado' => 'EN_MANTENIMIENTO'])->assertForbidden();
        $this->patch($url.'/estado', ['estado' => 'BLOQUEADO'])->assertForbidden();
        $this->post($url.'/bloqueos', [])->assertForbidden();
        $this->put($url, [...$data, 'nombre' => 'Cambio autorizado'])->assertRedirect();
        $this->assertSame('DISPONIBLE', $space->fresh()->estado->value);
        $this->assertDatabaseCount('recursos_institucionales', 1);
    }

    public function test_permission_must_be_supplied_by_same_eligible_role_and_revocation_takes_effect(): void
    {
        $actor = $this->user('Encargado de Laboratorio');
        $this->grantRole($actor, 'Docente');
        $permission = Permiso::where('codigo', 'espacios.gestionar')->sole();
        Rol::where('nombre', 'Docente')->sole()->permisos()->attach($permission);
        Rol::where('nombre', 'Encargado de Laboratorio')->sole()->permisos()->detach($permission);
        $this->login($actor);
        $this->post('/espacios', $this->spaceData())->assertForbidden();
        $this->get('/disponibilidad')->assertOk();
        foreach ($actor->rolesActivos as $role) {
            $role->permisos()->detach(Permiso::where('codigo', 'espacios.ver')->sole());
        }
        $this->get('/disponibilidad')->assertForbidden();
        $this->get('/espacios')->assertForbidden();
    }

    public function test_blocks_preserve_actor_and_history_and_other_overlapping_block_stays_active(): void
    {
        $actor = $this->user('Encargado de Laboratorio');
        $space = EspacioAcademico::factory()->create();
        $other = EspacioAcademico::factory()->create();
        $this->login($actor);
        $url = '/espacios/'.$space->getKey().'/bloqueos';
        $data = ['inicio' => '2027-01-10T08:00', 'fin' => '2027-01-10T09:00', 'motivo' => 'Actividad sintética'];
        $this->post($url, [...$data, 'responsable_id' => $this->user()->id])->assertSessionHasErrors('responsable_id');
        $this->post($url, [...$data, 'fin' => $data['inicio']])->assertSessionHasErrors('fin');
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, [...$data, 'motivo' => 'Segundo motivo sintético'])->assertSessionHasNoErrors()->assertRedirect();
        $blocks = $space->bloqueos()->orderBy('id')->get();
        $this->assertCount(2, $blocks);
        $this->assertSame($actor->id, $blocks[0]->responsable_id);
        $this->assertSame('2027-01-10 08:00', $blocks[0]->inicio->format('Y-m-d H:i'));
        $this->patch('/espacios/'.$other->getKey().'/bloqueos/'.$blocks[0]->id.'/desactivar')->assertNotFound();
        $this->patch($url.'/'.$blocks[0]->id.'/desactivar')->assertRedirect();
        $this->patch($url.'/'.$blocks[0]->id.'/desactivar')->assertRedirect();
        $this->assertFalse($blocks[0]->fresh()->activo);
        $this->assertTrue($blocks[1]->fresh()->activo);
        $this->assertSame('Actividad sintética', $blocks[0]->fresh()->motivo);
        $this->assertDatabaseCount('bloqueos_espacio', 2);
        $this->assertSame('DISPONIBLE', $space->fresh()->estado->value);
        $this->get($this->availabilityUrl(['q' => $space->recurso->codigo]))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 0));
    }

    public function test_auxiliary_catalogs_are_managed_paginated_and_unique(): void
    {
        $this->login($this->user('Encargado de Laboratorio'));
        foreach (['pisos' => ['numero' => 101, 'nombre' => 'Piso de prueba'], 'tipos' => ['codigo' => 'TIPO-DEMO', 'nombre' => 'Tipo de prueba'], 'caracteristicas' => ['nombre' => 'Característica de prueba', 'descripcion' => 'Sintética']] as $catalog => $data) {
            $url = '/espacios/catalogos/'.$catalog;
            $this->post($url, $data)->assertRedirect($url);
            $this->post($url, $data)->assertSessionHasErrors();
            $table = ['pisos' => 'pisos', 'tipos' => 'tipos_espacio', 'caracteristicas' => 'caracteristicas_espacio'][$catalog];
            $id = DB::table($table)->value('id');
            $this->put($url.'/'.$id, [...$data, 'nombre' => 'Nombre editado sintético'])->assertRedirect($url);
            $this->assertDatabaseHas($table, ['id' => $id, 'nombre' => 'Nombre editado sintético']);
            $this->get($url.'?q=0')->assertInertia(fn (Assert $p) => $p->where('registros.total', 0));
            $this->get($url)->assertOk()->assertInertia(fn (Assert $p) => $p->where('registros.total', 1)->where('registros.per_page', 15));
        }
        $this->get('/espacios/catalogos/politicas')->assertNotFound();
    }

    public function test_seed_addition_preserves_existing_revocations_and_only_grants_eligible_actions(): void
    {
        foreach (['Administrador' => 20, 'Encargado de Laboratorio' => 9, 'Docente' => 4, 'Estudiante' => 4] as $name => $count) {
            $this->assertSame($count, Rol::where('nombre', $name)->sole()->permisos()->count());
        }
        $permission = Permiso::where('codigo', 'espacios.gestionar')->sole();
        $role = Rol::where('nombre', 'Encargado de Laboratorio')->sole();
        $role->permisos()->detach($permission);
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($role->permisos()->whereKey($permission->id)->exists());
    }
}
