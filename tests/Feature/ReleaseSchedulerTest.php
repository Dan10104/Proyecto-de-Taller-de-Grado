<?php

namespace Tests\Feature;

use App\Models\EvaluacionLiberacion;
use App\Models\Reserva;
use App\Services\ReleaseScanService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\ReleaseConcurrencyTestCase;

class ReleaseSchedulerTest extends ReleaseConcurrencyTestCase
{
    public function test_candidates_keyset_batches_isolated_failures_and_manual_command(): void
    {
        config(['release.batch_size' => 2]);
        $locked = $this->reservation();
        $bad = $this->reservation();
        $good = $this->reservation(['modo' => 'AUTOMATICA']);
        $early = $this->reservation(['tolerancia_min' => 50]);
        $this->reservation([], ['estado' => 'PENDIENTE']);
        $this->reservation([], ['inicio' => now()->addHour(), 'fin' => now()->addHours(2)]);
        $this->reservation([], ['inicio' => now()->subHours(2), 'fin' => now()]);
        Reserva::factory()->aprobada()->create(); // No assigned policy.
        $worker = $this->start(['action' => 'evaluate', 'reservation' => $locked->id, 'hold' => 'reserve']);
        $this->go();
        $this->waitFor('.0.held');
        $fail = true;
        DB::connection()->beforeExecuting(function ($sql, $bindings) use ($bad, &$fail) {
            if ($fail && str_contains($sql, 'from `reservas`') && str_contains($sql, 'NOWAIT') && ($bindings[0] ?? null) === $bad->id) {
                $fail = false;
                throw new \RuntimeException('Synthetic per-reservation failure');
            }
        });
        $counts = app(ReleaseScanService::class)->run();
        $this->assertSame(['candidatas' => 4, 'evaluadas' => 1, 'omitidas' => 1, 'diferidas' => 1, 'errores' => 1], $counts);
        $this->assertSame('LIBERADA', $good->fresh()->estado->value);
        $this->assertSame('APROBADA', $bad->fresh()->estado->value);
        $this->assertSame('APROBADA', $early->fresh()->estado->value);
        $this->release(0);
        $this->assertSame('ALERTAR', $this->workerResult($worker)['status']);
        $this->artisan('reservas:evaluar-liberacion')->expectsOutput('Evaluadas: 1')->assertSuccessful();
        $this->artisan('reservas:evaluar-liberacion')->expectsOutput('Evaluadas: 0')->assertSuccessful();
        $this->assertSame(3, EvaluacionLiberacion::count());
    }

    public function test_minutely_scheduler_configuration_and_phase_boundaries(): void
    {
        $all = app(Schedule::class)->events();
        $this->assertCount(2, $all); // Phase 8 release evaluator and Phase 9 reconciler.
        $events = array_values(array_filter($all, fn ($event) => str_contains($event->command, 'reservas:evaluar-liberacion')));
        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events[0]->expression);
        $this->assertStringContainsString('reservas:evaluar-liberacion', $events[0]->command);
        $this->assertTrue($events[0]->withoutOverlapping);
        $this->assertFalse(Schema::hasTable('notificaciones'));
        $this->assertTrue(Schema::hasTable('registros_actividad'));
        $this->assertTrue(Schema::hasTable('evidencias_trazabilidad'));
        $this->assertFalse(DB::table('roles')->where('nombre', 'Sistema')->exists());
        $routes = collect(app('router')->getRoutes())->filter(fn ($r) => str_contains($r->uri(), 'liberacion'));
        $this->assertCount(9, $routes);
        $this->assertFalse($routes->contains(fn ($r) => str_contains($r->uri(), 'ejecutar') || str_contains($r->uri(), 'liberar')));
    }

    public function test_candidate_and_evidence_queries_have_official_index_plans_without_full_history(): void
    {
        $r = $this->reservation();
        $rows = [];
        for ($i = 0; $i < 500; $i++) {
            $rows[] = ['codigo' => 'SYN-RELEASE-PLAN-'.$i, 'espacio_id' => $r->espacio_id, 'solicitante_id' => $r->solicitante_id, 'inicio' => '2080-01-10 08:00:00', 'fin' => '2080-01-10 09:00:00', 'proposito' => 'Carga sintética', 'estado' => 'FINALIZADA'];
        }
        DB::table('reservas')->insert($rows);
        $query = app(ReleaseScanService::class)->candidates(CarbonImmutable::now())->select('id')->orderBy('id')->limit(100);
        $plan = DB::select('EXPLAIN FORMAT=TRADITIONAL '.$query->toSql(), $query->getBindings());
        $reservations = collect($plan)->firstWhere('table', 'reservas');
        $this->assertStringContainsString('idx_reserva_estado', $reservations->possible_keys);
        $this->assertSame('idx_reserva_estado', $reservations->key);
        $this->assertSame([$r->id], $query->pluck('id')->all());
        $sensor = $this->sensor($r);
        $sensorPlan = DB::select('EXPLAIN FORMAT=TRADITIONAL SELECT id FROM sensores_iot FORCE INDEX (idx_sensor_espacio) WHERE espacio_actual_id=? AND estado=? ORDER BY id FOR SHARE NOWAIT', [$r->espacio_id, 'HABILITADO']);
        $this->assertSame('idx_sensor_espacio', $sensorPlan[0]->key);
        $events = DB::select('EXPLAIN FORMAT=TRADITIONAL SELECT id FROM eventos_ocupacion WHERE espacio_historico_id=? AND sensor_id=? AND fecha_evento BETWEEN ? AND ? AND fecha_recepcion<=?', [$r->espacio_id, $sensor->id, now()->subMinutes(5), now(), now()]);
        $this->assertStringContainsString('idx_evento_', $events[0]->possible_keys);
    }
}
