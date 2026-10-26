<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\Reserva;
use App\Models\Rol;
use App\Services\AuthorizationService;
use App\Services\ReservationQueryService;
use App\Support\PermissionCatalog;
use Database\Seeders\SecuritySeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReservationTestCase;

class ReservationRequestTest extends ReservationTestCase
{
    public static function applicants(): array
    {
        return [['Docente'], ['Estudiante']];
    }

    #[DataProvider('applicants')]
    public function test_applicant_can_request_two_overlapping_pending_reservations(string $role): void
    {
        $u = $this->user($role);
        $s = EspacioAcademico::factory()->create();
        $data = $this->requestData($s);
        $this->login($u);
        $this->get('/reservas/crear?'.http_build_query($data))->assertOk()->assertInertia(fn (Assert $p) => $p->where('espacio.id', $s->getKey())->where('periodo.inicio', $data['inicio']));
        for ($i = 0; $i < 2; $i++) {
            $this->post('/reservas', $data)->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertDatabaseCount('reservas', 2);
        $rows = Reserva::all();
        $this->assertNotSame($rows[0]->codigo, $rows[1]->codigo);
        foreach ($rows as $r) {
            $this->assertSame($u->id, $r->solicitante_id);
            $this->assertSame('PENDIENTE', $r->estado->value);
            $this->assertNotNull($r->solicitada_en);
            $this->assertNull($r->resuelta_en);
        }
        $this->get('/reservas')->assertInertia(fn (Assert $p) => $p->where('reservas.total', 2)->missing('reservas.data.0.solicitante')->has('opciones.solicitantes', 0));
        $this->get('/reservas/'.$rows[0]->id)->assertInertia(fn (Assert $p) => $p->where('reserva.codigo', $rows[0]->codigo)->missing('reserva.solicitante_id')->missing('reserva.solicitante')->missing('reserva.password_hash'));
    }

    public static function invalidRequests(): array
    {
        return [[['proposito' => '   '], 'proposito'], [['proposito' => str_repeat('a', 501)], 'proposito'], [['inicio' => '2090-02-30T08:00'], 'inicio'], [['fin' => '2090-01-10T08:00'], 'fin'], [['fin' => '2090-01-10T07:59'], 'fin'], [['inicio' => 'now'], 'inicio'], [['espacio_id' => 999999], 'espacio_id'], [['codigo' => 'RES-FORZADO'], 'codigo'], [['estado' => 'APROBADA'], 'estado'], [['solicitante_id' => 999], 'solicitante_id'], [['resuelta_por_id' => 999], 'resuelta_por_id'], [['cancelada_por_id' => 999], 'cancelada_por_id'], [['solicitada_en' => '2090-01-01'], 'solicitada_en']];
    }

    #[DataProvider('invalidRequests')]
    public function test_invalid_or_forged_input_never_creates_a_row(array $bad, string $field): void
    {
        $this->login($this->user('Docente'));
        $s = EspacioAcademico::factory()->create();
        $this->post('/reservas', $this->requestData($s, $bad))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_guest_unassigned_admin_and_lab_manager_cannot_request_without_eligible_permission(): void
    {
        $s = EspacioAcademico::factory()->create();
        $this->post('/reservas', $this->requestData($s))->assertRedirect('/login');
        foreach ([null, 'Administrador', 'Encargado de Laboratorio'] as $role) {
            $u = $this->user($role);
            $this->login($u);
            $this->post('/reservas', $this->requestData($s))->assertForbidden();
            $this->post('/logout');
        }
        // Even a manually attached permission cannot bypass the frozen eligible-role matrix.
        Rol::where('nombre', 'Administrador')->sole()->permisos()->attach(Permiso::where('codigo', 'reservas.solicitar')->sole());
        $u = $this->user('Administrador');
        $this->assertFalse($u->can('reservas.solicitar'));
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_permission_matrix_and_idempotent_seeding_preserve_revocations(): void
    {
        foreach (['Administrador' => 5, 'Encargado de Laboratorio' => 5, 'Docente' => 3, 'Estudiante' => 3] as $role => $count) {
            $u = $this->user($role);
            $actual = array_values(array_filter(app(AuthorizationService::class)->permissions($u), fn ($p) => str_starts_with($p, 'reservas.')));
            $this->assertCount($count, $actual);
            $this->assertEqualsCanonicalizing(array_values(array_filter(PermissionCatalog::forRole($role), fn ($p) => str_starts_with($p, 'reservas.'))), $actual);
        }
        $role = Rol::where('nombre', 'Docente')->sole();
        $permission = Permiso::where('codigo', 'reservas.solicitar')->sole();
        $role->permisos()->detach($permission);
        $this->seed(SecuritySeeder::class);
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($this->user('Docente')->can('reservas.solicitar'));
        $this->assertDatabaseCount('permisos', 50);
    }

    public function test_ownership_filters_and_direct_access_cannot_be_expanded(): void
    {
        $owner = $this->user('Docente');
        $other = $this->user('Estudiante');
        $mine = $this->pending($owner);
        $foreign = $this->pending($other);
        $this->login($owner);
        $this->get('/reservas')->assertInertia(fn (Assert $p) => $p->where('reservas.total', 1)->where('reservas.data.0.id', $mine->id)->has('opciones.espacios', 1));
        $this->get('/reservas?alcance=administrativas')->assertForbidden();
        $this->get('/reservas?solicitante_id='.$other->id)->assertSessionHasErrors('solicitante_id');
        $this->get('/reservas?espacio_id='.$foreign->espacio_id)->assertInertia(fn (Assert $p) => $p->where('reservas.total', 0));
        $this->get('/reservas/'.$foreign->id)->assertForbidden();
        $this->post('/reservas/'.$foreign->id.'/cancelar')->assertForbidden();
        foreach (['aprobar', 'rechazar'] as $action) {
            $this->post('/reservas/'.$mine->id.'/'.$action)->assertForbidden();
        }
        $this->get('/reservas/999999')->assertNotFound();
    }

    public static function managers(): array
    {
        return [['Administrador'], ['Encargado de Laboratorio']];
    }

    #[DataProvider('managers')]
    public function test_administrative_list_filters_and_details(string $role): void
    {
        $a = $this->pending();
        $b = $this->pending(extra: ['estado' => 'RECHAZADA']);
        $this->login($this->user($role));
        $this->get('/reservas')->assertInertia(fn (Assert $p) => $p->where('alcance', 'administrativas')->where('reservas.total', 2)->has('reservas.data.0.solicitante.nombres')->missing('reservas.data.0.solicitante.correo'));
        foreach (['estado=PENDIENTE', 'solicitante_id='.$a->solicitante_id, 'espacio_id='.$a->espacio_id] as $filter) {
            $this->get('/reservas?'.$filter)->assertInertia(fn (Assert $p) => $p->where('reservas.total', 1)->where('reservas.data.0.id', $a->id));
        }
        $this->get('/reservas?fecha=2090-01-10')->assertInertia(fn (Assert $p) => $p->where('reservas.total', 2));
        $this->get('/reservas?fecha=2090-01-11')->assertInertia(fn (Assert $p) => $p->where('reservas.total', 0));
        $this->get('/reservas?alcance=propias')->assertInertia(fn (Assert $p) => $p->where('reservas.total', 0));
        $this->get('/reservas/'.$b->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('reserva.acciones.resolver', false)->where('reserva.acciones.cancelar', false));
        foreach (['estado=ACTIVA', 'fecha=2090-02-30', 'alcance=todas'] as $bad) {
            $this->get('/reservas?'.$bad)->assertSessionHasErrors();
        }
    }

    public function test_pagination_and_eager_loading_do_not_grow_queries_per_reservation(): void
    {
        $u = $this->user('Administrador');
        $s = EspacioAcademico::factory()->create();
        $owner = $this->user('Docente');
        Reserva::factory()->count(18)->create(['espacio_id' => $s->getKey(), 'solicitante_id' => $owner->id]);
        $this->login($u);
        $this->get('/reservas')->assertInertia(fn (Assert $p) => $p->has('reservas.data', 15)->where('reservas.total', 18));
        $this->get('/reservas?page=2')->assertInertia(fn (Assert $p) => $p->has('reservas.data', 3));
        $service = app(ReservationQueryService::class);
        $permissions = app(AuthorizationService::class)->permissions($u);
        $counts = [];
        foreach ([1, 15] as $limit) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $rows = $service->filtered($u, true, [])->limit($limit)->get();
            foreach ($rows as $r) {
                $service->present($r, $u, $permissions, true);
            } $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(7, $counts[1]);
    }
}
