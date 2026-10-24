<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Support\PermissionCatalog;
use Database\Seeders\SecuritySeeder;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SecurityTestCase;

class RoleManagementTest extends SecurityTestCase
{
    public function test_official_missing_role_can_be_created_and_duplicates_rejected(): void
    {
        $admin = $this->user('Administrador');
        Rol::where('nombre', 'Estudiante')->delete();
        $this->login($admin);
        $data = ['nombre' => 'Estudiante', 'descripcion' => 'Rol institucional de estudiantes.'];
        $this->post('/roles', $data)->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', $data);
        $this->post('/roles', $data)->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('roles', 4);
    }

    public function test_new_institutional_identities_are_not_invented(): void
    {
        $this->login($this->user('Administrador'));
        $this->post('/roles', ['nombre' => 'Superadmin'])->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('roles', 4);
    }

    public function test_role_description_can_be_updated_without_renaming_history(): void
    {
        $this->login($this->user('Administrador'));
        $role = Rol::where('nombre', 'Docente')->first();
        $this->put('/roles/'.$role->id, ['nombre' => 'Docente', 'descripcion' => 'Descripción actualizada'])->assertRedirect('/roles');
        $this->assertSame('Descripción actualizada', $role->fresh()->descripcion);
        Rol::where('nombre', 'Estudiante')->delete();
        $this->put('/roles/'.$role->id, ['nombre' => 'Estudiante'])->assertSessionHasErrors('nombre');
        $this->assertSame('Docente', $role->fresh()->nombre);
    }

    public function test_permissions_can_be_removed_and_restored_without_admin_bypass(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $role = Rol::where('nombre', 'Administrador')->first();
        $ids = Permiso::whereIn('codigo', PermissionCatalog::forRole('Administrador'))->pluck('id')->all();
        $edit = Permiso::where('codigo', 'usuarios.editar')->sole();
        $this->put('/roles/'.$role->id.'/permisos', ['permisos' => array_values(array_diff($ids, [$edit->id]))])->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/usuarios/'.$admin->id.'/editar')->assertForbidden();
        $this->put('/roles/'.$role->id.'/permisos', ['permisos' => $ids])->assertRedirect();
        $this->get('/usuarios/'.$admin->id.'/editar')->assertOk();
    }

    public function test_recovery_permissions_cannot_be_removed(): void
    {
        $this->login($this->user('Administrador'));
        $role = Rol::where('nombre', 'Administrador')->first();
        $this->put('/roles/'.$role->id.'/permisos', ['permisos' => []])->assertSessionHasErrors('administracion');
        $this->assertSame(count(PermissionCatalog::forRole('Administrador')), $role->permisos()->count());
    }

    public function test_permission_selection_must_be_real_and_actor_compatible(): void
    {
        $this->login($this->user('Administrador'));
        $admin = Rol::where('nombre', 'Administrador')->first();
        $teacher = Rol::where('nombre', 'Docente')->first();
        $this->put('/roles/'.$admin->id.'/permisos', ['permisos' => [999999]])->assertSessionHasErrors('permisos.0');
        $this->put('/roles/'.$teacher->id.'/permisos', ['permisos' => [Permiso::where('codigo', 'usuarios.ver')->sole()->id]])->assertSessionHasErrors('permisos');
        $this->assertEqualsCanonicalizing(['qr.validar_uso_espacio', 'equipamientos.ver', 'prestamos.ver_propios', 'prestamos.solicitar', 'espacios.ver', 'reservas.ver_propias', 'reservas.solicitar', 'reservas.cancelar_propias'], $teacher->permisos()->pluck('codigo')->all());
    }

    public function test_assignment_retirement_and_reassignment_preserve_history(): void
    {
        $admin = $this->user('Administrador');
        $target = $this->user();
        $role = Rol::where('nombre', 'Docente')->first();
        $this->login($admin);
        $url = '/roles/'.$role->id.'/asignaciones';
        $this->post($url, ['usuario_id' => $target->id])->assertRedirect();
        $first = $target->asignaciones()->sole();
        $this->assertNull($first->fecha_retiro);
        $this->post($url, ['usuario_id' => $target->id])->assertSessionHasErrors('rol_id');
        $this->assertSame(1, $target->asignaciones()->count());
        $this->delete($url, ['usuario_id' => $target->id])->assertRedirect();
        $first->refresh();
        $this->assertSame($admin->id, $first->retirado_por_id);
        $this->assertTrue($first->fecha_retiro->greaterThanOrEqualTo($first->fecha_asignacion));
        $this->assertSame(0, $target->rolesActivos()->count());
        $this->post($url, ['usuario_id' => $target->id])->assertRedirect();
        $this->assertSame(2, $target->asignaciones()->count());
        $this->assertSame(1, $target->asignaciones()->activas()->count());
    }

    public function test_last_administrator_role_cannot_be_withdrawn(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $role = Rol::where('nombre', 'Administrador')->first();
        $this->delete('/roles/'.$role->id.'/asignaciones', ['usuario_id' => $admin->id])->assertSessionHasErrors('administracion');
        $this->assertSame(1, $admin->asignaciones()->activas()->count());
    }

    public function test_withdrawal_closes_existing_duplicate_rows_without_deleting_them(): void
    {
        $admin = $this->user('Administrador');
        $target = $this->user('Docente');
        $this->grantRole($target, 'Docente');
        $this->login($admin);
        $role = Rol::where('nombre', 'Docente')->first();
        $this->delete('/roles/'.$role->id.'/asignaciones', ['usuario_id' => $target->id])->assertRedirect();
        $this->assertSame(2, $target->asignaciones()->count());
        $this->assertSame(0, $target->asignaciones()->activas()->count());
    }

    public function test_repeating_seeders_preserves_permission_removal(): void
    {
        $role = Rol::where('nombre', 'Administrador')->first();
        $permission = Permiso::where('codigo', 'usuarios.editar')->first();
        $role->permisos()->detach($permission);
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($role->permisos()->whereKey($permission->id)->exists());
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('permisos', count(PermissionCatalog::DEFINITIONS));
    }

    public static function nonAdministrativeRoles(): array
    {
        return [['Docente'], ['Estudiante'], ['Encargado de Laboratorio']];
    }

    #[DataProvider('nonAdministrativeRoles')]
    public function test_initiator_rules_deny_other_roles_even_with_incorrectly_associated_permission(string $name): void
    {
        $user = $this->user($name);
        $user->rolesActivos()->first()->permisos()->sync(Permiso::pluck('id'));
        $this->login($user);
        $this->get('/inicio')->assertOk()->assertInertia(fn (Assert $p) => $p->where('auth.permisos', fn ($codes) => $codes->sort()->values()->all() === collect(PermissionCatalog::forRole($name))->sort()->values()->all()));
        foreach (['/usuarios', '/usuarios/crear', '/roles', '/roles/crear', '/permisos', '/asignaciones/candidatos?q=Persona'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/usuarios', $this->userData())->assertForbidden();
        $this->post('/roles', ['nombre' => 'Administrador'])->assertForbidden();
    }

    public function test_permission_from_ineligible_role_cannot_be_combined_with_admin_identity(): void
    {
        $user = $this->user('Administrador');
        $this->grantRole($user, 'Docente');
        $edit = Permiso::where('codigo', 'usuarios.editar')->first();
        Rol::where('nombre', 'Administrador')->first()->permisos()->detach($edit);
        Rol::where('nombre', 'Docente')->first()->permisos()->attach($edit);
        $this->login($user);
        $this->get('/usuarios/'.$user->id.'/editar')->assertForbidden();
    }

    public function test_role_and_permission_pages_expose_only_safe_data(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $role = Rol::where('nombre', 'Administrador')->first();
        $this->get('/roles')->assertOk();
        $this->get('/permisos')->assertOk();
        $this->get('/roles/'.$role->id)->assertOk()->assertInertia(fn (Assert $p) => $p->has('catalogo', count(PermissionCatalog::forRole('Administrador')))->missing('usuarios.data.0.password_hash'));
        $this->get('/asignaciones/candidatos?q=Persona')->assertOk()->assertDontSee('password_hash')->assertDontSee('token_hash');
    }

    public function test_retiring_admin_role_takes_effect_on_next_request(): void
    {
        $actor = $this->user('Administrador');
        $this->user('Administrador');
        $this->login($actor);
        $role = Rol::where('nombre', 'Administrador')->first();
        $this->delete('/roles/'.$role->id.'/asignaciones', ['usuario_id' => $actor->id])->assertRedirect();
        $this->get('/usuarios')->assertForbidden();
        $this->get('/inicio')->assertOk();
    }
}
