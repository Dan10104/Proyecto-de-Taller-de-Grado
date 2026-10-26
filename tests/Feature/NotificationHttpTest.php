<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Services\Notifications\NotificationReadService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\SecurityTestCase;
use Tests\Support\EnforceCsrf;

#[Group('notifications')]
class NotificationHttpTest extends SecurityTestCase
{
    public static function roles(): array
    {
        return [['Administrador'], ['Encargado de Laboratorio'], ['Docente'], ['Estudiante']];
    }

    #[DataProvider('roles')]
    public function test_each_role_owns_list_detail_counter_and_cannot_access_foreign_id(string $role): void
    {
        $u = $this->user($role);
        $own = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        $other = Notificacion::factory()->create();
        $this->login($u);
        $this->withCredentials();
        $this->get('/notificaciones')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Notifications/Index')->has('notificaciones.data', 1)->where('notificaciones.data.0.id', $own->id)->where('auth.no_leidas', 1));
        $this->get('/notificaciones/'.$own->id)->assertOk()->assertInertia(fn (Assert $p) => $p->component('Notifications/Show')->where('notificacion.mensaje', $own->mensaje)->missing('notificacion.destinatario_id')->missing('notificacion.registro_actividad_id')->missing('notificacion.registro_actividad'));
        foreach ([$other->id, 99999999] as $id) {
            $this->get('/notificaciones/'.$id)->assertNotFound();
            $this->postJson('/notificaciones/'.$id.'/leer')->assertNotFound();
        }
        $this->assertNull($own->fresh()->leida_en);
        $this->assertNull($other->fresh()->leida_en);
    }

    public function test_read_ignores_client_date_and_owner_and_keeps_first_server_timestamp(): void
    {
        $u = $this->user('Docente');
        $n = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        $other = Notificacion::factory()->create();
        $this->login($u);
        $this->withCredentials();
        $this->freezeTime();
        $this->postJson('/notificaciones/'.$n->id.'/leer', ['leida_en' => '2001-01-01', 'destinatario_id' => $other->destinatario_id])->assertOk()->assertJson(['no_leidas' => 0]);
        $first = $n->fresh()->leida_en;
        $this->assertSame(now()->toDateTimeString(), $first->toDateTimeString());
        $this->travel(1)->minutes();
        $this->postJson('/notificaciones/'.$n->id.'/leer')->assertOk();
        $this->assertTrue($first->eq($n->fresh()->leida_en));
        $this->assertSame($u->id, $n->fresh()->destinatario_id);
        $this->assertNull($other->fresh()->leida_en);
        $this->assertSame(0, app(NotificationReadService::class)->unread($u));
    }

    public function test_get_prefetch_and_list_never_mutate_but_permission_loss_between_get_post_denies(): void
    {
        $u = $this->user('Administrador');
        $n = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        $this->login($u);
        $this->withCredentials();
        $this->get('/notificaciones/'.$n->id, ['Purpose' => 'prefetch', 'X-Inertia' => 'true', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))])->assertOk();
        $this->get('/notificaciones?lectura=no_leidas')->assertOk();
        $this->assertNull($n->fresh()->leida_en);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'notificaciones.ver_propias')->sole());
        $this->postJson('/notificaciones/'.$n->id.'/leer')->assertForbidden();
        $this->get('/notificaciones')->assertForbidden();
        $this->assertNull($n->fresh()->leida_en);
        $this->assertDatabaseCount('notificaciones', 1);
    }

    public function test_csrf_missing_or_invalid_cannot_mark_read(): void
    {
        $u = $this->user('Estudiante');
        $n = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        $this->login($u);
        $this->withCredentials();
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        $this->post('/notificaciones/'.$n->id.'/leer')->assertStatus(419);
        $this->post('/notificaciones/'.$n->id.'/leer', ['_token' => 'invalid'])->assertStatus(419);
        $this->assertNull($n->fresh()->leida_en);
    }

    public function test_filter_order_pagination_count_and_empty_use_only_owner(): void
    {
        $u = $this->user('Docente');
        $first = Notificacion::factory()->create(['destinatario_id' => $u->id, 'creada_en' => '2026-01-01 10:00:00']);
        for ($i = 0; $i < 17; $i++) {
            Notificacion::factory()->create(['destinatario_id' => $u->id, 'registro_actividad_id' => $first->registro_actividad_id, 'tipo' => $i < 3 ? 'DECISION' : 'SOLICITUD', 'creada_en' => '2026-01-02 10:00:00', 'leida_en' => $i < 2 ? '2026-01-02 11:00:00' : null]);
        }
        Notificacion::factory()->create();
        $this->login($u);
        $this->withCredentials();
        $last = Notificacion::where('destinatario_id', $u->id)->max('id');
        $this->get('/notificaciones')->assertOk()->assertInertia(fn (Assert $p) => $p->has('notificaciones.data', 15)->where('notificaciones.total', 18)->where('notificaciones.data.0.id', $last)->where('auth.no_leidas', 16));
        $this->get('/notificaciones?page=2')->assertOk()->assertInertia(fn (Assert $p) => $p->has('notificaciones.data', 3)->where('notificaciones.data.2.id', $first->id));
        $this->get('/notificaciones?lectura=leidas&tipo=DECISION')->assertOk()->assertInertia(fn (Assert $p) => $p->has('notificaciones.data', 2)->where('auth.no_leidas', 16));
        $this->get('/notificaciones?lectura=no_leidas&tipo=DECISION')->assertOk()->assertInertia(fn (Assert $p) => $p->has('notificaciones.data', 1));
        $this->get('/notificaciones?tipo=INCIDENCIA')->assertOk()->assertInertia(fn (Assert $p) => $p->has('notificaciones.data', 0));
        $this->getJson('/notificaciones?lectura=all&tipo=BLOCKCHAIN')->assertUnprocessable();
    }

    public function test_history_survives_object_permission_loss_and_link_is_reauthorized(): void
    {
        $u = $this->user('Docente');
        $n = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        DB::table('reservas')->where('id', $n->registroActividad->reserva_id)->update(['solicitante_id' => $u->id]);
        $this->login($u);
        $this->withCredentials();
        $this->get('/notificaciones/'.$n->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('enlace.url', '/reservas/'.$n->registroActividad->reserva_id));
        Rol::where('nombre', 'Docente')->sole()->permisos()->detach(Permiso::where('codigo', 'reservas.ver_propias')->sole());
        $this->get('/notificaciones/'.$n->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('enlace', null)->where('notificacion.mensaje', $n->mensaje));
        $this->get('/reservas/'.$n->registroActividad->reserva_id)->assertForbidden();
    }

    public function test_anonymous_inactive_and_removed_role_have_no_access(): void
    {
        $u = $this->user('Docente');
        $n = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        $this->get('/notificaciones')->assertRedirect('/login');
        $this->login($u);
        $this->withCredentials();
        DB::table('asignaciones_roles')->where('usuario_id', $u->id)->update(['fecha_retiro' => now()]);
        $this->get('/notificaciones')->assertForbidden();
        $u->forceFill(['estado' => 'INACTIVO'])->save();
        $this->postJson('/notificaciones/'.$n->id.'/leer')->assertUnauthorized();
        $this->assertNull($n->fresh()->leida_en);
    }

    public function test_no_delete_unread_all_or_global_activity_routes_exist(): void
    {
        $this->login($this->user('Administrador'));
        foreach (['/notificaciones/todas', '/notificaciones/1/no-leida'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->delete('/notificaciones/1')->assertStatus(405);
        // CU30 is now independently authorized; notifications do not grant it.
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'actividad.ver')->value('id'));
        $this->get('/actividad')->assertForbidden();
    }
}
