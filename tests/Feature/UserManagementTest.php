<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Models\AsignacionRol;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SesionAcceso;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\SecurityTestCase;

class UserManagementTest extends SecurityTestCase
{
    public function test_authorized_administrator_can_search_filter_and_view_users(): void
    {
        $admin = $this->user('Administrador');
        $target = $this->user('Docente');
        $target->fill(['nombres' => 'Encontrable'])->save();
        $this->login($admin);
        $role = Rol::where('nombre', 'Docente')->first();
        $this->get('/usuarios?q=Encontrable&estado=ACTIVO&rol='.$role->id)->assertOk()->assertInertia(fn (Assert $p) => $p->component('Users/Index')->has('usuarios.data', 1)->where('usuarios.data.0.id', $target->id)->missing('usuarios.data.0.password_hash'));
        $this->get('/usuarios/'.$target->id)->assertOk()->assertInertia(fn (Assert $p) => $p->component('Users/Show')->has('historial.data', 1)->missing('usuario.password_hash'));
    }

    public function test_creation_hashes_password_and_assigns_role_transactionally(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $role = Rol::where('nombre', 'Docente')->first();
        $data = $this->userData(['roles' => [$role->id]]);
        $this->post('/usuarios', $data)->assertRedirect('/usuarios');
        $user = Usuario::where('correo', $data['correo'])->sole();
        $this->assertTrue(Hash::check($data['password'], $user->password_hash));
        $this->assertSame('argon2id', password_get_info($user->password_hash)['algoName']);
        $this->assertDatabaseHas('asignaciones_roles', ['usuario_id' => $user->id, 'rol_id' => $role->id, 'asignado_por_id' => $admin->id, 'fecha_retiro' => null]);
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $this->post('/usuarios', $this->userData(['correo' => strtoupper($admin->correo)]))->assertSessionHasErrors('correo');
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_password_confirmation_and_length_are_validated(): void
    {
        $this->login($this->user('Administrador'));
        $this->post('/usuarios', $this->userData(['password' => 'short', 'password_confirmation' => 'other']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_update_preserves_password_unless_explicitly_changed(): void
    {
        $admin = $this->user('Administrador');
        $user = $this->user();
        $hash = $user->password_hash;
        $this->login($admin);
        $this->put('/usuarios/'.$user->id, ['nombres' => 'Actualizada', 'apellidos' => $user->apellidos, 'correo' => $user->correo])->assertRedirect('/usuarios');
        $this->assertSame('Actualizada', $user->fresh()->nombres);
        $this->assertSame($hash, $user->fresh()->password_hash);
    }

    public function test_password_change_revokes_all_target_sessions(): void
    {
        $admin = $this->user('Administrador');
        $user = $this->user();
        $session = (new SesionAcceso)->forceFill(['usuario_id' => $user->id, 'token_hash' => hash('sha256', random_bytes(32)), 'creada_en' => now(), 'expira_en' => now()->addHour()]);
        $session->save();
        $this->login($admin);
        $new = bin2hex(random_bytes(20));
        $this->put('/usuarios/'.$user->id, ['nombres' => $user->nombres, 'apellidos' => $user->apellidos, 'correo' => $user->correo, 'password' => $new, 'password_confirmation' => $new])->assertRedirect('/usuarios');
        $this->assertTrue(Hash::check($new, $user->fresh()->password_hash));
        $this->assertNotNull($session->fresh()->revocada_en);
    }

    public function test_disable_preserves_history_and_revokes_sessions(): void
    {
        $admin = $this->user('Administrador');
        $target = $this->user('Docente');
        $history = $target->asignaciones()->sole();
        $session = (new SesionAcceso)->forceFill(['usuario_id' => $target->id, 'token_hash' => hash('sha256', random_bytes(32)), 'creada_en' => now(), 'expira_en' => now()->addHour()]);
        $session->save();
        $this->login($admin);
        $this->patch('/usuarios/'.$target->id.'/deshabilitar')->assertRedirect();
        $this->assertSame(EstadoUsuario::INACTIVO, $target->fresh()->estado);
        $this->assertNotNull($session->fresh()->revocada_en);
        $this->assertNotNull(AsignacionRol::find($history->id));
        $this->post('/logout');
        $this->post('/login', ['correo' => $target->correo, 'password' => $this->password])->assertSessionHasErrors('correo');
    }

    public function test_last_active_administrator_cannot_be_disabled(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $this->patch('/usuarios/'.$admin->id.'/deshabilitar')->assertSessionHasErrors('administracion');
        $this->assertSame(EstadoUsuario::ACTIVO, $admin->fresh()->estado);
        $this->assertNull(SesionAcceso::sole()->revocada_en);
    }

    public function test_sensitive_mass_assignment_is_rejected(): void
    {
        $this->login($this->user('Administrador'));
        $this->post('/usuarios', $this->userData(['estado' => 'INACTIVO', 'password_hash' => 'attacker-supplied']))->assertSessionHasErrors(['estado', 'password_hash']);
        $this->assertDatabaseCount('usuarios', 1);
        $user = new Usuario(['nombres' => 'Test', 'password_hash' => 'untrusted', 'estado' => 'INACTIVO']);
        $this->assertNull($user->password_hash);
        $this->assertNull($user->estado);
    }

    public function test_initial_role_assignment_requires_its_own_permission(): void
    {
        $admin = $this->user('Administrador');
        $admin->rolesActivos()->first()->permisos()->detach(Permiso::where('codigo', 'roles.asignar')->value('id'));
        $this->login($admin);
        $this->post('/usuarios', $this->userData(['roles' => [Rol::where('nombre', 'Docente')->value('id')]]))->assertSessionHasErrors('roles');
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_missing_user_returns_not_found(): void
    {
        $this->login($this->user('Administrador'));
        $this->get('/usuarios/99999999')->assertNotFound();
    }

    public function test_user_list_is_paginated(): void
    {
        $admin = $this->user('Administrador');
        Usuario::factory()->count(16)->create();
        $this->login($admin);
        $this->get('/usuarios')->assertInertia(fn (Assert $p) => $p->has('usuarios.data', 15)->where('usuarios.total', 17));
    }
}
