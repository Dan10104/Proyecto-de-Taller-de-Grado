<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Services\AuthorizationService;
use App\Services\EquipmentAvailabilityService;
use App\Services\IncidentMaintenanceService;
use App\Services\LoanQueryService;
use App\Services\ResourceOperationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\LoanTestCase;

class EquipmentAvailabilityTest extends LoanTestCase
{
    public static function periods(): array
    {
        return [
            'equal' => ['08:00', '09:00', false], 'contained' => ['08:15', '08:45', false], 'contains' => ['07:00', '10:00', false],
            'partial-left' => ['07:00', '08:01', false], 'partial-right' => ['08:59', '10:00', false],
            'adjacent-left' => ['07:00', '08:00', true], 'adjacent-right' => ['09:00', '10:00', true],
        ];
    }

    #[DataProvider('periods')]
    public function test_half_open_periods_match_query_and_transaction(string $start, string $end, bool $available): void
    {
        $admin = $this->user('Administrador');
        $p = $this->loan();
        $this->loans()->approve($admin, $p);
        $e = $p->equipamiento;
        $service = app(EquipmentAvailabilityService::class);
        [$a,$b] = $service->period('2090-01-10T'.$start, '2090-01-10T'.$end);
        $this->assertSame($available, $service->candidates([], $a, $b)->whereKey($e->getKey())->exists());
        $check = fn () => app(ResourceOperationService::class)->transaction($admin, 'equipamientos.ver', function () use ($service, $e, $a, $b) {
            $c = app(ResourceOperationService::class)->lock($e->getKey(), true);
            $service->assertAvailable($c, $a, $b);
        });
        if ($available) {
            $check();
            $this->assertTrue(true);
        } else {
            $this->conflict($check);
        }
    }

    public function test_pending_rejected_returned_do_not_block_but_delivered_blocks_outside_period(): void
    {
        $admin = $this->user('Administrador');
        $p = $this->loan();
        $e = $p->equipamiento;
        $service = app(EquipmentAvailabilityService::class);
        [$a,$b] = $service->period('2090-01-10T08:00', '2090-01-10T09:00');
        foreach (['PENDIENTE', 'RECHAZADO', 'DEVUELTO'] as $state) {
            $p->forceFill(['estado' => $state])->save();
            $this->assertTrue($service->candidates([], $a, $b)->whereKey($e->getKey())->exists());
        }
        $p->forceFill(['estado' => 'ENTREGADO', 'inicio_previsto' => '2000-01-01 08:00', 'fin_previsto' => '2000-01-01 09:00'])->save();
        $this->assertFalse($service->candidates([], $a, $b)->whereKey($e->getKey())->exists());
        $this->conflict(fn () => $this->loans()->create($this->user('Docente'), $this->loanData($e)));
    }

    public function test_physical_restrictions_and_open_future_incident_exclude_candidates(): void
    {
        $admin = $this->user('Administrador');
        $service = app(EquipmentAvailabilityService::class);
        [$a,$b] = $service->period('2090-01-10T08:00', '2090-01-10T09:00');
        foreach (['PRESTADO', 'EN_MANTENIMIENTO', 'BAJA'] as $state) {
            $e = EquipamientoTecnologico::factory()->create(['estado' => $state]);
            $this->assertFalse($service->candidates([], $a, $b)->whereKey($e->getKey())->exists());
            $this->conflict(fn () => $this->loans()->create($this->user('Docente'), $this->loanData($e)));
        }
        $e = EquipamientoTecnologico::factory()->create();
        app(IncidentMaintenanceService::class)->open($admin, $this->incidentData($e->getKey(), ['inicio' => '2091-01-01T08:00', 'fin_previsto' => '2091-01-01T09:00']));
        $e->forceFill(['estado' => 'DISPONIBLE'])->save(); // Defensive query must not trust a stale physical label.
        $this->assertFalse($service->candidates([], $a, $b)->whereKey($e->getKey())->exists());
        $this->conflict(fn () => $this->loans()->create($this->user('Docente'), $this->loanData($e)));
    }

    public function test_filters_conjoin_and_incomplete_period_does_not_promise_availability(): void
    {
        $e = EquipamientoTecnologico::factory()->create(['espacio_base_id' => EspacioAcademico::factory()]);
        $s = app(EquipmentAvailabilityService::class);
        $filters = ['q' => $e->recurso->codigo, 'tipo_equipamiento_id' => $e->tipo_equipamiento_id, 'marca' => $e->marca, 'modelo' => $e->modelo, 'espacio_base_id' => $e->espacio_base_id, 'piso_id' => $e->espacioBase->piso_id];
        $this->assertSame([$e->getKey()], $s->matching($filters)->pluck('recurso_id')->all());
        $this->assertSame(0, $s->matching([...$filters, 'marca' => 'INEXISTENTE'])->count());
        $this->login($this->user('Estudiante'));
        $this->get('/equipamientos/disponibilidad')->assertOk()->assertInertia(fn ($p) => $p->where('equipamientos', null)->where('periodoCompleto', false));
        $this->get('/equipamientos/disponibilidad?inicio_previsto=2090-01-10T08%3A00')->assertSessionHasErrors('fin_previsto');
    }

    public function test_sql_candidates_are_paginated_and_thousand_record_queries_under_three_seconds(): void
    {
        $u = $this->user('Docente');
        $e = EquipamientoTecnologico::factory()->create();
        $type = $e->tipo_equipamiento_id;
        $resources = [];
        for ($i = 0; $i < 1000; $i++) {
            $resources[] = ['codigo' => 'SYN-PERF-'.$i, 'nombre' => 'Equipo de rendimiento '.$i];
        }
        DB::table('recursos_institucionales')->insert($resources);
        $ids = DB::table('recursos_institucionales')->where('codigo', 'like', 'SYN-PERF-%')->pluck('id');
        DB::table('equipamientos_tecnologicos')->insert($ids->map(fn ($id) => ['recurso_id' => $id, 'tipo_equipamiento_id' => $type, 'estado' => 'DISPONIBLE'])->all());
        $loans = [];
        foreach ($ids as $i => $id) {
            $loans[] = ['codigo' => 'PRE-'.strtolower((string) Str::uuid()), 'equipamiento_id' => $id, 'solicitante_id' => $u->id, 'inicio_previsto' => '2090-01-10 08:00:00', 'fin_previsto' => '2090-01-10 09:00:00', 'motivo' => 'Rendimiento sintético', 'estado' => 'PENDIENTE'];
        }DB::table('prestamos')->insert($loans);
        $s = app(EquipmentAvailabilityService::class);
        [$a,$b] = $s->period('2090-01-10T08:00', '2090-01-10T09:00');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = microtime(true);
        $result = $s->candidates([], $a, $b)->paginate(15);
        foreach ($result as $item) {
            app(LoanQueryService::class)->equipment($item);
        }$catalogTime = microtime(true) - $start;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertLessThan(3, $catalogTime);
        $this->assertSame(1001, $result->total());
        $this->assertLessThanOrEqual(8, count($queries));
        $this->assertStringContainsString('not exists', $queries[1]['query']);
        $q = app(LoanQueryService::class);
        $permissions = app(AuthorizationService::class)->permissions($u);
        $start = microtime(true);
        $page = $q->filtered($u, false, [])->paginate(15);
        foreach ($page as $loan) {
            $q->present($loan, false, $permissions);
        }$loanTime = microtime(true) - $start;
        $this->assertLessThan(3, $loanTime);
        $this->assertSame(1000,$page->total());
        $this->assertSame(15,$page->perPage());
        fwrite(STDERR,sprintf("\nFase5 rendimiento MySQL: catálogo %.4fs; préstamos %.4fs; catálogo %d consultas.\n",$catalogTime,$loanTime,count($queries)));
    }
}
