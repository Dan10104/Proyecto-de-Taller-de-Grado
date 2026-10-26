<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use Database\Seeders\SecuritySeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SecurityTestCase;

class SystemAuthorizationTest extends SecurityTestCase
{
    public static function roles(): array
    {
        return [['Administrador'], ['Encargado de Laboratorio'], ['Docente'], ['Estudiante']];
    }

    #[DataProvider('roles')]
    public function test_global_navigation_matrix_uses_effective_permissions(string $role): void
    {
        $this->login($this->user($role));
        $manager = in_array($role, ['Administrador', 'Encargado de Laboratorio'], true);
        foreach (['/inicio', '/dashboard', '/notificaciones', '/asistente', '/espacios', '/disponibilidad', '/equipamientos', '/reservas', '/prestamos', '/liberacion/evaluaciones'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/reportes', '/incidencias', '/qr', '/iot/sensores', '/iot/monitoreo', '/liberacion/politicas'] as $url) {
            $this->get($url)->assertStatus($manager ? 200 : 403);
        }
        foreach (['/usuarios', '/roles', '/permisos', '/trazabilidad', '/actividad'] as $url) {
            $this->get($url)->assertStatus($role === 'Administrador' ? 200 : 403);
        }
    }

    public static function revocations(): array
    {
        return [
            ['usuarios.ver', '/usuarios'], ['reservas.ver_administrativas', '/reservas?alcance=administrativas'],
            ['prestamos.ver_administrativos', '/prestamos?alcance=administrativos'], ['qr.gestionar', '/qr'],
            ['iot.sensores.ver', '/iot/sensores'], ['iot.monitoreo.ver', '/iot/monitoreo'],
            ['liberacion.politicas.ver', '/liberacion/politicas'], ['liberacion.evaluaciones.ver_administrativas', '/liberacion/evaluaciones?alcance=administrativas'],
            ['trazabilidad.evidencias.ver', '/trazabilidad'], ['notificaciones.ver_propias', '/notificaciones'],
            ['dashboard.ver', '/dashboard'], ['reportes.ver', '/reportes'], ['chatbot.consultar', '/asistente'], ['actividad.ver', '/actividad'],
        ];
    }

    #[DataProvider('revocations')]
    public function test_revocation_during_same_session_takes_effect_next_request_and_seeder_cannot_restore_it(string $permission, string $url): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $this->get($url)->assertOk();
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', $permission)->value('id'));
        $this->seed(SecuritySeeder::class);
        $this->get($url)->assertForbidden();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_export_request_is_reauthorized_after_permission_revocation(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $this->get('/reportes?reporte=reservas')->assertOk();
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'reportes.exportar')->value('id'));
        $this->withCredentials()->postJson('/reportes/exportar', ['reporte' => 'reservas', 'formato' => 'pdf', 'filtros' => []])->assertForbidden();
        $this->assertDatabaseMissing('registros_actividad', ['accion' => 'REPORTE_EXPORTADO']);
    }
}
