<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use App\Models\Notificacion;
use App\Models\Prestamo;
use App\Models\Reserva;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SecurityTestCase;
use Tests\Support\EnforceCsrf;

class SystemAttackSurfaceTest extends SecurityTestCase
{
    public function test_every_mutable_web_route_rejects_missing_csrf_before_business_execution(): void
    {
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! in_array('web', $route->gatherMiddleware(), true)) {
                continue;
            }
            foreach (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) as $method) {
                $uri = '/'.preg_replace_callback('/\{([^}]+)\}/', fn ($m) => match ($m[1]) {
                    'catalogo' => 'pisos','objetivoTipo' => 'recurso',default => '1'
                }, $route->uri());
                $this->call($method, $uri)->assertStatus(419);
                $checked++;
            }
        }
        $this->assertGreaterThanOrEqual(45, $checked);
        $this->assertDatabaseCount('registros_actividad', 0);
    }

    public static function filters(): array
    {
        $sql = '1 OR 1=1 --';

        return [
            ['/disponibilidad', ['piso_id' => $sql]], ['/equipamientos/disponibilidad', ['tipo_equipamiento_id' => $sql]],
            ['/reservas', ['espacio_id' => $sql]], ['/prestamos', ['equipamiento_id' => $sql]],
            ['/incidencias', ['recurso_id' => $sql]], ['/iot/monitoreo', ['sensor_id' => $sql]],
            ['/liberacion/evaluaciones', ['politica_id' => $sql]], ['/actividad', ['actor_id' => $sql]],
            ['/reportes', ['reporte' => 'reservas', 'filtros' => ['orderBy' => '(SELECT SLEEP(10))']]],
            ['/reportes', ['reporte' => '../../.env', 'filtros' => []]],
        ];
    }

    #[DataProvider('filters')]
    public function test_malformed_filters_and_sql_or_path_payloads_are_rejected(string $path, array $query): void
    {
        $this->login($this->user('Administrador'));
        $this->withCredentials();
        $this->getJson($path.'?'.http_build_query($query))->assertUnprocessable()->assertDontSee('SQLSTATE', false);
        $this->assertDatabaseCount('registros_actividad', 0);
    }

    public static function personalRoles(): array
    {
        return [['Docente'], ['Estudiante']];
    }

    #[DataProvider('personalRoles')]
    public function test_foreign_ids_and_forged_scope_cannot_cross_personal_boundaries(string $role): void
    {
        $owner = $this->user($role);
        $foreign = $this->user($role);
        $r = Reserva::factory()->create(['solicitante_id' => $foreign->id]);
        $p = Prestamo::factory()->create(['solicitante_id' => $foreign->id]);
        $n = Notificacion::factory()->create(['destinatario_id' => $foreign->id]);
        $e = EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id]);
        $own = Reserva::factory()->create(['solicitante_id' => $owner->id]);
        $this->login($owner);
        $this->withCredentials();
        $this->get('/reservas/'.$own->id)->assertOk();
        foreach (['/reservas/'.$r->id, '/reservas/'.$r->id.'/qr', '/prestamos/'.$p->id] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->postJson('/reservas/'.$r->id.'/cancelar')->assertForbidden();
        $this->postJson('/prestamos/'.$p->id.'/entregar')->assertForbidden();
        $this->get('/notificaciones/'.$n->id)->assertNotFound();
        $this->postJson('/notificaciones/'.$n->id.'/leer')->assertNotFound();
        $this->get('/liberacion/evaluaciones/'.$e->id)->assertNotFound();
        $this->get('/reservas?alcance=administrativas')->assertForbidden();
        $this->get('/prestamos?alcance=administrativos')->assertForbidden();
        $this->get('/reservas?solicitante_id='.$foreign->id)->assertDontSee($r->codigo, false);
        $this->get('/prestamos?solicitante_id='.$foreign->id)->assertDontSee($p->codigo, false);
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
        $this->assertSame('PENDIENTE', $p->fresh()->estado->value);
        $this->assertNull($n->fresh()->leida_en);
    }

    public function test_mass_assignment_cannot_override_owners_codes_decisions_or_audit_actor(): void
    {
        $owner = $this->user('Docente');
        $admin = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        $equipment = EquipamientoTecnologico::factory()->create();
        $this->login($owner);
        $this->withCredentials();
        $start = now()->addDays(3)->setTime(8, 0)->format('Y-m-d\TH:i');
        $end = now()->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i');
        $reservation = ['espacio_id' => $space->getKey(), 'inicio' => $start, 'fin' => $end, 'proposito' => 'Solicitud sintética'];
        $loan = ['equipamiento_id' => $equipment->getKey(), 'inicio_previsto' => $start, 'fin_previsto' => $end, 'motivo' => 'Solicitud sintética'];
        foreach (['solicitante_id' => $admin->id, 'codigo' => 'FORGED', 'estado' => 'APROBADA', 'resuelta_por_id' => $admin->id, 'cancelada_por_id' => $admin->id] as $field => $value) {
            $this->postJson('/reservas', [...$reservation, $field => $value])->assertUnprocessable();
        }
        foreach (['solicitante_id' => $admin->id, 'codigo' => 'FORGED', 'estado' => 'ENTREGADO', 'resuelto_por_id' => $admin->id, 'entregado_por_id' => $admin->id, 'recibido_por_id' => $admin->id, 'confirmado_por_id' => $admin->id] as $field => $value) {
            $this->postJson('/prestamos', [...$loan, $field => $value])->assertUnprocessable();
        }
        $this->assertDatabaseCount('reservas', 0);
        $this->assertDatabaseCount('prestamos', 0);
        $this->assertDatabaseCount('registros_actividad', 0);
        $this->postJson('/reservas', [...$reservation, 'actor_id' => $admin->id, 'critica' => true])->assertRedirect();
        $this->assertDatabaseHas('reservas', ['solicitante_id' => $owner->id, 'estado' => 'PENDIENTE']);
        $this->assertDatabaseHas('registros_actividad', ['accion' => 'RESERVA_SOLICITADA', 'actor_id' => $owner->id, 'critica' => false]);
        $this->assertDatabaseMissing('registros_actividad', ['actor_id' => $admin->id]);
    }
}
