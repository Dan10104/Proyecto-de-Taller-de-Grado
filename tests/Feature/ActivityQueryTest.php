<?php

namespace Tests\Feature;

use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Activity\ActivityFilters;
use App\Services\Activity\ActivityQueryService;
use App\Support\PermissionCatalog;
use Carbon\CarbonImmutable;
use Database\Seeders\SecuritySeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ActivityTestCase;

class ActivityQueryTest extends ActivityTestCase
{
    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2090-03-01 12:00:00', 'America/La_Paz'));
        $this->admin = $this->user('Administrador');
    }

    private function row(array $attributes = []): int
    {
        return DB::table('registros_actividad')->insertGetId([...['actor_id' => $this->admin->id, 'accion' => 'USUARIO_CREADO', 'fecha_hora' => now(), 'resultado' => 'EXITO', 'critica' => false], ...$attributes]);
    }

    private function ids(array $filters = []): array
    {
        return app(ActivityQueryService::class)->query($this->admin, ActivityFilters::from($filters))->pluck('a.id')->all();
    }

    private function revoke(string $code): void
    {
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', $code)->value('id'));
    }

    public function test_admin_permission_increment_is_revocable_and_seeding_does_not_restore_it(): void
    {
        $this->assertSame(['Administrador'], PermissionCatalog::eligibleRoles('actividad.ver'));
        $this->assertNotContains('actividad.ver', PermissionCatalog::RECOVERY);
        $this->login($this->admin);
        $id = $this->row();
        $this->get('/actividad')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertInertia(fn (Assert $p) => $p->component('Activity/Index')->has('registros.data', 1));
        $this->get('/actividad/'.$id)->assertOk();
        $this->revoke('actividad.ver');
        $this->seed(SecuritySeeder::class);
        $this->get('/actividad')->assertForbidden();
        $this->get('/actividad/'.$id)->assertForbidden();
    }

    public static function deniedRoles(): array
    {
        return [['Encargado de Laboratorio'], ['Docente'], ['Estudiante']];
    }

    #[DataProvider('deniedRoles')]
    public function test_other_roles_even_with_mistaken_grant_cannot_list_or_read(string $name): void
    {
        $user = $this->user($name);
        Rol::where('nombre', $name)->sole()->permisos()->syncWithoutDetaching([Permiso::where('codigo', 'actividad.ver')->value('id')]);
        $this->login($user);
        $this->get('/actividad')->assertForbidden();
        $this->get('/actividad/'.$this->row())->assertForbidden();
    }

    public function test_default_thirty_local_days_inclusive_start_exclusive_next_day_and_stable_ties(): void
    {
        $this->row(['fecha_hora' => '2090-01-30 23:59:59']);
        $start = $this->row(['fecha_hora' => '2090-01-31 00:00:00']);
        $a = $this->row(['fecha_hora' => '2090-03-01 23:59:59']);
        $b = $this->row(['fecha_hora' => '2090-03-01 23:59:59']);
        $this->row(['fecha_hora' => '2090-03-02 00:00:00']);
        $this->assertSame([$b, $a, $start], $this->ids());
        $this->assertSame([$b, $a], $this->ids(['fecha_desde' => '2090-03-01', 'fecha_hasta' => '2090-03-01']));
        $dto = app(ActivityQueryService::class)->detail($this->admin, $start);
        $this->assertSame('2090-01-31T00:00:00-04:00', $dto['fecha_hora']);
    }

    public function test_pagination_is_twenty_five_and_preserves_filters(): void
    {
        for ($n = 0; $n < 27; $n++) {
            $this->row();
        }
        $result = app(ActivityQueryService::class)->listing($this->admin, ActivityFilters::from(['accion' => 'USUARIO_CREADO']));
        $this->assertCount(25, $result['registros']->items());
        $this->assertSame(27, $result['registros']->total());
        $this->assertStringContainsString('accion=USUARIO_CREADO', $result['registros']->nextPageUrl());
        $second = app(ActivityQueryService::class)->listing($this->admin, ActivityFilters::from(['accion' => 'USUARIO_CREADO', 'page' => 2]));
        $this->assertCount(2, $second['registros']->items());
    }

    public function test_all_exact_filters_and_combination_use_and_including_mixed_historical_responsibility(): void
    {
        $owner = $this->user('Docente');
        $reserve = Reserva::factory()->create(['solicitante_id' => $owner->id]);
        $loan = Prestamo::factory()->create(['solicitante_id' => $owner->id]);
        $incident = IncidenciaMantenimiento::factory()->create(['recurso_id' => $reserve->espacio_id]);
        $filters = ['actor_id' => $this->admin->id, 'usuario_afectado_id' => $owner->id, 'reserva_id' => $reserve->id, 'prestamo_id' => $loan->id, 'incidencia_id' => $incident->id, 'recurso_id' => $reserve->espacio_id, 'accion' => 'RESERVA_APROBADA', 'resultado' => 'EXITO', 'critica' => 1, 'proceso_responsable' => 'scheduler:liberacion'];
        $id = $this->row($filters);
        $this->row(['actor_id' => $owner->id, 'accion' => 'USUARIO_EDITADO', 'resultado' => 'ERROR']);
        foreach ($filters as $key => $value) {
            $this->assertSame([$id], $this->ids([$key => $value]), $key);
        }
        $this->assertSame([$id], $this->ids($filters));
        $this->assertSame([], $this->ids([...$filters, 'resultado' => 'RECHAZO']));
        $this->assertContains($id, $this->ids(['responsable_tipo' => 'humano']));
        $this->assertSame([$id], $this->ids(['responsable_tipo' => 'proceso']));
        $this->assertSame([], $this->ids(['reserva_id' => 999999]));
    }

    public function test_eight_entity_categories_derive_only_from_foreign_keys_without_duplicates(): void
    {
        $r = Reserva::factory()->create();
        $p = Prestamo::factory()->create();
        $i = IncidenciaMantenimiento::factory()->create(['recurso_id' => $r->espacio_id]);
        $space = $this->row(['usuario_afectado_id' => $r->solicitante_id, 'recurso_id' => $r->espacio_id, 'reserva_id' => $r->id, 'incidencia_id' => $i->id]);
        $equipment = $this->row(['recurso_id' => $p->equipamiento_id, 'prestamo_id' => $p->id]);
        $none = $this->row(['descripcion' => 'Reserva #'.$r->id]);
        foreach (['USUARIO' => [$space], 'RECURSO' => [$equipment, $space], 'ESPACIO' => [$space], 'EQUIPAMIENTO' => [$equipment], 'RESERVA' => [$space], 'PRESTAMO' => [$equipment], 'INCIDENCIA' => [$space], 'SIN_REFERENCIA' => [$none]] as $type => $ids) {
            $this->assertSame($ids, $this->ids(['tipo_entidad' => $type]));
        }
    }

    public static function invalidFilters(): array
    {
        return array_map(fn ($data) => [$data], [
            ['accion' => 'ACCION_57'], ['resultado' => 'OK'], ['critica' => 'true'], ['tipo_entidad' => 'SENSOR'], ['proceso_responsable' => '%'], ['responsable_tipo' => 'sistema'],
            ['actor_id' => '1 OR 1=1'], ['actor_id' => -1], ['usuario_afectado_id' => []], ['reserva_id' => 0], ['prestamo_id' => '1.2'], ['recurso_id' => 'abc'], ['incidencia_id' => true],
            ['page' => 0], ['page' => []], ['orderBy' => 'id'], ['descripcion' => 'texto'], ['fecha_desde' => '2090-03-01'], ['fecha_hasta' => '2090-03-01'],
            ['fecha_desde' => '2090-03-02', 'fecha_hasta' => '2090-03-01'], ['fecha_desde' => '2090-02-30', 'fecha_hasta' => '2090-03-01'], ['fecha_desde' => '1000-01-01', 'fecha_hasta' => '9999-12-31'],
        ]);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_or_unknown_filter_is_rejected(array $data): void
    {
        $this->expectException(ValidationException::class);
        ActivityFilters::from($data);
    }

    public function test_detail_privacy_current_names_inactive_actor_and_safe_unknown_history(): void
    {
        $user = $this->user();
        $user->forceFill(['estado' => 'INACTIVO'])->save();
        $id = $this->row(['actor_id' => $user->id, 'usuario_afectado_id' => $user->id, 'accion' => 'UNKNOWN-PRIVATE', 'descripcion' => 'SECRET-PRIVATE', 'proceso_responsable' => 'SECRET-PROCESS']);
        $dto = app(ActivityQueryService::class)->detail($this->admin, $id);
        $this->assertTrue($dto['actor']['nombre_actual']);
        $this->assertStringContainsString($user->nombres, $dto['actor']['label']);
        $this->assertNull($dto['accion']);
        $this->assertNull($dto['descripcion']);
        $this->assertSame('Proceso interno', $dto['proceso_responsable']);
        $this->revoke('usuarios.ver');
        $safe = app(ActivityQueryService::class)->detail($this->admin, $id);
        $this->assertSame('Usuario #'.$user->id, $safe['actor']['label']);
        $this->assertNull($safe['actor']['url']);
        $json = json_encode($safe);
        foreach (['SECRET', 'UNKNOWN-PRIVATE', $user->correo, $user->password_hash, $user->nombres] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
        $this->assertSame('SECRET-PRIVATE', DB::table('registros_actividad')->where('id', $id)->value('descripcion'));
    }

    public function test_domain_links_disappear_when_current_permissions_are_revoked(): void
    {
        $r = Reserva::factory()->create();
        $p = Prestamo::factory()->create();
        $i = IncidenciaMantenimiento::factory()->create(['recurso_id' => $r->espacio_id]);
        $id = $this->row(['reserva_id' => $r->id, 'prestamo_id' => $p->id, 'incidencia_id' => $i->id, 'recurso_id' => $r->espacio_id, 'usuario_afectado_id' => $r->solicitante_id]);
        $before = app(ActivityQueryService::class)->detail($this->admin, $id);
        foreach ($before['referencias'] as $ref) {
            $this->assertNotNull($ref['url']);
        }
        foreach (['reservas.ver_administrativas', 'prestamos.ver_administrativos', 'incidencias.ver', 'espacios.ver', 'usuarios.ver'] as $permission) {
            $this->revoke($permission);
        }
        $after = app(ActivityQueryService::class)->detail($this->admin, $id);
        foreach ($after['referencias'] as $ref) {
            $this->assertNull($ref['url']);
            $this->assertGreaterThan(0, $ref['id']);
        }
    }

    public function test_read_only_routes_and_unknown_detail_do_not_create_activity(): void
    {
        $this->login($this->admin);
        $id = $this->row();
        $this->get('/actividad')->assertOk();
        $this->get('/actividad/'.$id)->assertOk();
        $this->get('/actividad/999999')->assertNotFound();
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->$method('/actividad/'.$id)->assertStatus(405);
        }
        $this->post('/actividad')->assertStatus(405);
        $this->assertDatabaseCount('registros_actividad', 1);
    }
}
