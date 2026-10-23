<?php

namespace Tests;

use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\SecuritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SecuritySeeder::class);
        $this->password = bin2hex(random_bytes(16));
    }

    protected function user(?string $role = null): Usuario
    {
        $user = Usuario::factory()->create(['password_hash' => Hash::make($this->password)]);
        if ($role) {
            $this->grantRole($user, $role);
        }

        return $user;
    }

    protected function grantRole(Usuario $user, string $name): void
    {
        (new AsignacionRol)->forceFill(['usuario_id' => $user->id, 'rol_id' => Rol::where('nombre', $name)->firstOrFail()->id, 'asignado_por_id' => $user->id, 'fecha_asignacion' => now()])->save();
    }

    protected function login(Usuario $user): void
    {
        $this->post('/login', ['correo' => $user->correo, 'password' => $this->password])->assertRedirect('/inicio');
        $this->withCookie(config('session.cookie'), session()->getId());
        Auth::forgetGuards();
    }

    protected function userData(array $extra = []): array
    {
        return [...['nombres' => 'Persona', 'apellidos' => 'Sintética', 'correo' => 'persona-'.bin2hex(random_bytes(4)).'@example.test', 'password' => $this->password, 'password_confirmation' => $this->password], ...$extra];
    }
}
