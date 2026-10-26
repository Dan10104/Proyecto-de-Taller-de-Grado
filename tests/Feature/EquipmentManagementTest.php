<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\Permiso;
use App\Models\RecursoInstitucional;
use App\Models\Rol;
use App\Models\TipoEquipamiento;
use App\Services\AuthorizationService;
use App\Services\EquipmentService;
use App\Support\PermissionCatalog;
use Database\Seeders\SecuritySeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\LoanTestCase;

class EquipmentManagementTest extends LoanTestCase
{
    public function test_shared_identity_nullable_unique_series_edit_and_no_orphan_on_failure(): void
    {
        $a = $this->user('Administrador');
        $service = app(EquipmentService::class);
        $e = $service->save($a, null, $this->equipmentData(['numero_serie' => '  ']));
        $f = $service->save($a, null, $this->equipmentData());
        $this->assertSame($e->getKey(), $e->recurso->id);
        $this->assertFalse($e->getIncrementing());
        $this->assertNull($e->numero_serie);
        $this->assertNull($f->numero_serie);
        $service->save($a, $e, $this->equipmentData(['codigo' => $e->recurso->codigo, 'numero_serie' => 'SINTETICA-001']));
        $this->assertSame('SINTETICA-001', $e->fresh()->numero_serie);
        foreach ([['numero_serie' => 'SINTETICA-001'], ['codigo' => $e->recurso->codigo]] as $bad) {
            try {
                $service->save($a, null, $this->equipmentData($bad));
                $this->fail();
            } catch (ValidationException) {
                $this->assertDatabaseCount('equipamientos_tecnologicos', 2);
            }
        }
        $count = RecursoInstitucional::count();
        EquipamientoTecnologico::creating(function () {
            throw new \RuntimeException('Fallo sintético después del padre');
        });
        try {
            $service->save($a, null, $this->equipmentData());
            $this->fail();
        } catch (\RuntimeException $ex) {
            $this->assertSame('Fallo sintético después del padre', $ex->getMessage());
        } finally {
            EquipamientoTecnologico::flushEventListeners();
        }
        $this->assertSame($count, RecursoInstitucional::count());
    }

    public function test_inventory_crud_types_and_no_delete_endpoint(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $this->login($a);
        $this->post('/equipamientos/tipos', ['codigo' => 'TIP-SYN', 'nombre' => 'Tipo sintético'])->assertRedirect();
        $type = TipoEquipamiento::where('codigo', 'TIP-SYN')->sole();
        $this->put('/equipamientos/tipos/'.$type->id, ['codigo' => 'TIP-SYN', 'nombre' => 'Tipo editado'])->assertRedirect();
        $this->assertSame('Tipo editado', $type->fresh()->nombre);
        $data = $this->equipmentData(['tipo_equipamiento_id' => $type->id]);
        $this->post('/equipamientos', $data)->assertRedirect();
        $e = EquipamientoTecnologico::sole();
        $this->get('/equipamientos/'.$e->getKey())->assertOk()->assertInertia(fn (Assert $p) => $p->component('Equipment/Show')->where('equipamiento.nombre', $data['nombre']));
        $this->put('/equipamientos/'.$e->getKey(), [...$data, 'nombre' => 'Equipo editado'])->assertRedirect();
        $this->assertSame('Equipo editado', $e->recurso->fresh()->nombre);
        $this->get('/equipamientos')->assertOk();
        $this->get('/equipamientos/crear')->assertOk();
        $this->get('/equipamientos/'.$e->getKey().'/editar')->assertOk();
        $this->get('/equipamientos/tipos')->assertOk();
        $this->delete('/equipamientos/'.$e->getKey())->assertStatus(405);
    }

    public function test_new_permission_matrix_and_seed_preserve_withdrawals(): void
    {
        foreach (['Administrador' => 10, 'Encargado de Laboratorio' => 10, 'Docente' => 3, 'Estudiante' => 3] as $role => $count) {
            $u = $this->user($role);
            $permissions = array_values(array_filter(app(AuthorizationService::class)->permissions($u), fn ($p) => preg_match('/^(equipamientos|prestamos|incidencias)\./', $p)));
            $this->assertCount($count, $permissions);
            foreach (PermissionCatalog::DEFINITIONS as $code => $definition) {
                if (preg_match('/^(equipamientos|prestamos|incidencias)\./', $code)) {
                    $this->assertSame(in_array($role, PermissionCatalog::eligibleRoles($code), true), $u->can($code), $role.' '.$code);
                }
            }
        }
        $role = Rol::where('nombre', 'Administrador')->sole();
        $permission = Permiso::where('codigo', 'prestamos.registrar_entrega')->sole();
        $role->permisos()->detach($permission);
        $this->seed(SecuritySeeder::class);
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($this->user('Administrador')->can('prestamos.registrar_entrega'));
        $this->assertDatabaseCount('permisos', 51);
        $role->permisos()->attach(Permiso::where('codigo', 'prestamos.solicitar')->sole());
        $this->assertFalse($this->user('Administrador')->can('prestamos.solicitar'));
        $this->grantRole($u = $this->user('Administrador'), 'Docente');
        $this->assertTrue($u->can('prestamos.solicitar'));
    }

    public function test_state_change_permission_no_manual_custody_and_explicit_aptitude(): void
    {
        $admin = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $s = app(EquipmentService::class);
        $this->conflict(fn () => $s->state($admin, $e, 'PRESTADO'));
        $s->state($admin, $e, 'BAJA');
        $this->assertSame('BAJA', $e->fresh()->estado->value);
        try {
            $s->state($admin, $e, 'DISPONIBLE');
            $this->fail();
        } catch (ValidationException) {
            $this->assertSame('BAJA', $e->fresh()->estado->value);
        }
        $s->state($admin, $e, 'DISPONIBLE', true);
        $this->assertSame('DISPONIBLE', $e->fresh()->estado->value);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'equipamientos.cambiar_estado')->sole());
        $this->expectException(AuthorizationException::class);
        $s->save($admin, $e, $this->equipmentData(['codigo' => $e->recurso->codigo, 'estado' => 'BAJA']));
    }

    public function test_student_cannot_mutate_inventory_and_inactive_actor_cannot_use_service(): void
    {
        $u = $this->user('Estudiante');
        $this->login($u);
        $this->get('/equipamientos')->assertOk();
        $this->get('/equipamientos/crear')->assertForbidden();
        $this->post('/equipamientos', $this->equipmentData())->assertForbidden();
        $this->get('/incidencias')->assertForbidden();
        $u->forceFill(['estado' => 'INACTIVO'])->save();
        $this->expectException(AuthorizationException::class);
        $this->loans()->create($u, $this->loanData(EquipamientoTecnologico::factory()->create()));
    }
}
