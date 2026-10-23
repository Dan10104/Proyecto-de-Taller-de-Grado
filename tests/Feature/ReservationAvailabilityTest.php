<?php

namespace Tests\Feature;

use App\Exceptions\ReservationConflictException;
use App\Models\EspacioAcademico;
use App\Services\SpaceAvailabilityService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReservationTestCase;

class ReservationAvailabilityTest extends ReservationTestCase
{
    public static function states(): array
    {
        return [['PENDIENTE', 1], ['APROBADA', 0], ['RECHAZADA', 1], ['CANCELADA', 1], ['LIBERADA', 1], ['FINALIZADA', 1]];
    }

    #[DataProvider('states')]
    public function test_only_approved_overlapping_reservation_excludes_space(string $state, int $total): void
    {
        $r = $this->pending(extra: ['estado' => $state]);
        $this->login($r->solicitante);
        $this->get($this->availabilityUrl(['fecha' => '2090-01-10']))->assertInertia(fn (Assert $p) => $p->where('espacios.total', $total));
    }

    public static function periods(): array
    {
        return [['07:00', '08:00', 1], ['09:00', '10:00', 1], ['07:30', '08:30', 0], ['08:30', '09:30', 0], ['08:15', '08:45', 0], ['07:00', '10:00', 0]];
    }

    #[DataProvider('periods')]
    public function test_approved_reservations_use_half_open_intervals(string $start, string $end, int $total): void
    {
        $r = $this->pending(extra: ['estado' => 'APROBADA', 'inicio' => '2090-01-10 '.$start, 'fin' => '2090-01-10 '.$end]);
        $this->login($r->solicitante);
        $this->get($this->availabilityUrl(['fecha' => '2090-01-10']))->assertInertia(fn (Assert $p) => $p->where('espacios.total', $total));
    }

    public function test_conflict_lookup_can_use_official_period_index_without_loading_reservations(): void
    {
        $r = $this->pending();
        $rows = [];
        for ($i = 0; $i < 500; $i++) {
            $rows[] = ['codigo' => 'SYNTHETIC-'.$i, 'espacio_id' => $r->espacio_id, 'solicitante_id' => $r->solicitante_id, 'inicio' => '2091-01-10 08:00', 'fin' => '2091-01-10 09:00', 'estado' => 'APROBADA', 'proposito' => 'Prueba de consulta en BD'];
        }
        DB::table('reservas')->insert($rows);
        $plan = DB::select('EXPLAIN FORMAT=TRADITIONAL SELECT id FROM reservas WHERE espacio_id=? AND estado=? AND inicio<? AND fin>? LIMIT 1', [$r->espacio_id, 'APROBADA', $r->fin->toDateTimeString(), $r->inicio->toDateTimeString()]);
        $this->assertStringContainsString('idx_reserva_espacio_periodo', $plan[0]->possible_keys);
        $this->assertSame('idx_reserva_espacio_periodo', $plan[0]->key);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(1, app(SpaceAvailabilityService::class)->candidates([], $r->inicio, $r->fin)->count());
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('not exists', $queries[0]['query']);
        $this->assertStringContainsString('reservas', $queries[0]['query']);
    }

    public function test_midnight_request_approval_and_local_date_filters_agree(): void
    {
        $owner = $this->user('Docente');
        $space = EspacioAcademico::factory()->create();
        $r = $this->service()->create($owner, $this->requestData($space, ['inicio' => '2090-01-10T23:30', 'fin' => '2090-01-11T00:30']));
        $this->service()->approve($this->user('Administrador'), $r);
        $this->login($owner);
        foreach (['2090-01-10', '2090-01-11'] as $day) {
            $this->get('/reservas?fecha='.$day)->assertInertia(fn (Assert $p) => $p->where('reservas.total', 1));
        }
        $this->get('/reservas?fecha=2090-01-12')->assertInertia(fn (Assert $p) => $p->where('reservas.total', 0));
        $this->get($this->availabilityUrl(['fecha' => '2090-01-11', 'hora_inicio' => '00:00', 'hora_fin' => '01:00']))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 0));
        $this->get($this->availabilityUrl(['fecha' => '2090-01-11', 'hora_inicio' => '00:30', 'hora_fin' => '01:00']))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 1));
        $this->expectException(ReservationConflictException::class);
        $this->service()->create($owner, $this->requestData($space, ['inicio' => '2090-01-11T00:00', 'fin' => '2090-01-11T01:00']));
    }

    public function test_expired_approved_and_other_space_do_not_block_or_change_state(): void
    {
        $r = $this->pending(extra: ['inicio' => '2000-01-10 08:00', 'fin' => '2000-01-10 09:00', 'estado' => 'APROBADA']);
        $other = $this->pending(extra: ['estado' => 'APROBADA']);
        $this->login($r->solicitante);
        $this->get($this->availabilityUrl(['fecha' => '2090-01-10']))->assertInertia(fn (Assert $p) => $p->where('espacios.total', 1)->where('espacios.data.0.recurso_id', $r->espacio_id));
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertSame('APROBADA', $other->fresh()->estado->value);
    }
}
