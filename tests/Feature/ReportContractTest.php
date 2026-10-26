<?php

namespace Tests\Feature;

use App\Models\EvaluacionLiberacion;
use App\Models\EventoOcupacion;
use App\Models\IncidenciaMantenimiento;
use App\Models\RegistroActividad;
use App\Services\DashboardService;
use App\Services\IotMonitoringService;
use App\Services\Reporting\ReportBudget;
use App\Services\Reporting\ReportExportService;
use App\Services\Reporting\ReportGenerationService;
use App\Services\Reporting\ReportQueryService;
use App\Services\Reporting\ReportTemporaryStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReportingTestCase;

class ReportContractTest extends ReportingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['reporting.memory_bytes' => 256 * 1024 * 1024]);
    }

    #[DataProvider('formatsAndReports')]
    public function test_seven_report_projections_and_order_match_materialized_dataset_and_valid_export(string $id, string $format): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $this->loanRow();
        IncidenciaMantenimiento::factory()->create();
        EventoOcupacion::factory()->create(['fecha_recepcion' => now()]);
        $this->activityFor($r, $a);
        $q = app(ReportQueryService::class);
        $p = $q->preview($a, $id, []);
        $store = app(ReportTemporaryStore::class);
        $dir = $store->create();
        try {
            $data = $q->materialize($q->prepare($a, $id, [], true), $a, $dir, new ReportBudget);
            $this->assertSame($p['filas'], iterator_to_array($data->rows()));
            $output = app(ReportExportService::class)->render($data, $format, $dir, new ReportBudget);
            $this->assertFileExists($output['file']);
            $this->assertGreaterThan(500, filesize($output['file']));
            $this->assertSame($p['columnas'], $data->metadata['columnas']);
        } finally {
            $store->remove($dir);
        }
        $this->assertSame(0, RegistroActividad::where('accion', 'REPORTE_EXPORTADO')->count());
    }

    public static function formatsAndReports(): array
    {
        $cases = [];
        foreach (['reservas', 'espacios', 'equipamiento', 'prestamos', 'incidencias', 'iot', 'actividad-operacional'] as $id) {
            foreach (['pdf', 'xlsx'] as $format) {
                $cases[] = [$id, $format];
            }
        }

        return $cases;
    }

    public function test_revocation_during_generation_invalidates_file_and_does_not_audit(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $this->reservation();
        $p = $this->report('reservas', [], $a);
        $real = new ReportExportService;
        $this->mock(ReportExportService::class)->shouldReceive('render')->once()->andReturnUsing(function (...$args) use ($real) {
            $out = $real->render(...$args);
            $this->revoke('reportes.exportar');

            return $out;
        });
        try {
            app(ReportGenerationService::class)->generate($a, 'reservas', [], 'xlsx', $p['firma_preview']);
            $this->fail('Revocation must invalidate');
        } catch (AuthorizationException) {
            $this->assertSame(0, RegistroActividad::where('accion', 'REPORTE_EXPORTADO')->count());
        }
    }

    public function test_changed_filters_and_midnight_require_new_preview(): void
    {
        $a = $this->user('Administrador');
        $this->reservation();
        $p = $this->report('reservas', [], $a);
        foreach ([['estado' => 'PENDIENTE'], []] as $f) {
            if ($f === []) {
                $this->travelTo(now()->addDay());
            }try {
                app(ReportGenerationService::class)->generate($a, 'reservas', $f, 'pdf', $p['firma_preview']);
                $this->fail('Stale preview');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('vista previa', $e->getMessage());
            }
        }
    }

    public function test_iot_reader_failure_is_insufficient_instead_of_false_zero(): void
    {
        $this->mock(IotMonitoringService::class)->shouldReceive('summary')->once()->andThrow(new \RuntimeException('Synthetic unavailable'));
        $d = app(DashboardService::class)->read($this->user('Administrador'), []);
        $this->assertNull(collect($d['tarjetas'])->keyBy('id')['A10']['valor']);
        $this->assertTrue($d['iot']['error']);
    }

    public function test_activity_excludes_invalid_context_and_orders_numeric_references(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $this->activityFor($r, $a, ['critica' => true]);
        $this->activityFor($r, $a, ['recurso_id' => null]);
        $this->activityFor($r, $a, ['actor_id' => null, 'proceso_responsable' => 'unexpected']);
        $this->assertSame(0, $this->report('actividad-operacional', [], $a)['total']);
        $this->activityFor($r, $a);
        $this->assertSame(1, $this->report('actividad-operacional', [], $a)['total']);
    }

    public function test_activity_release_requires_exact_linked_evaluation(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $e = EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id, 'resultado' => 'LIBERAR']);
        $extra = ['accion' => 'RESERVA_LIBERADA', 'actor_id' => null, 'proceso_responsable' => 'scheduler:liberacion', 'descripcion' => 'Evaluación CU23 #'.$e->id.'; política #'.$e->politica_id.'.'];
        $this->activityFor($r, $a, $extra);
        $this->activityFor($r, $a, [...$extra, 'descripcion' => 'Inventada']);
        $p = $this->report('actividad-operacional', [], $a);
        $this->assertSame(1, $p['total']);
        $this->assertSame(1, $p['filas'][0]['cantidad']);
        $this->assertSame('Liberación automática', $p['filas'][0]['responsable']);
    }

    public function test_activity_export_counts_only_previous_own_exports(): void
    {
        $a = $this->user('Administrador');
        $b = $this->user('Administrador');
        $this->reservation();
        $p = $this->report('reservas', [], $b);
        $response = app(ReportGenerationService::class)->generate($b, 'reservas', [], 'xlsx', $p['firma_preview']);
        $response->cleanup();
        $this->assertSame(0, $this->report('actividad-operacional', [], $a)['total']);
        $p = $this->report('actividad-operacional', [], $b);
        $this->assertSame(1, $p['filas'][0]['cantidad']);
        $response = app(ReportGenerationService::class)->generate($b, 'actividad-operacional', [], 'xlsx', $p['firma_preview']);
        $response->cleanup();
        $this->assertSame(2, $this->report('actividad-operacional', [], $b)['filas'][0]['cantidad']);
    }

    public function test_read_permission_and_export_permission_are_independent(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $this->reservation();
        $this->login($a);
        $this->withCredentials();
        $this->revoke('reportes.exportar');
        $this->get('/reportes?reporte=reservas')->assertOk();
        $this->postJson('/reportes/exportar', ['reporte' => 'reservas', 'formato' => 'pdf'])->assertForbidden();
        $this->revoke('reportes.ver');
        $this->get('/reportes')->assertForbidden();
        $this->getJson('/reportes/opciones?reporte=reservas&campo=espacio_id')->assertForbidden();
    }

    public function test_download_send_content_also_removes_temporary(): void
    {
        $a = $this->user('Administrador');
        $this->reservation();
        $p = $this->report('reservas', [], $a);
        $response = app(ReportGenerationService::class)->generate($a, 'reservas', [], 'xlsx', $p['firma_preview']);
        $file = $response->getFile()->getPathname();
        ob_start();
        $response->sendContent();
        $bytes = ob_get_clean();
        $this->assertStringStartsWith('PK', $bytes);
        $this->assertFileDoesNotExist($file);
    }

    public function test_extreme_dashboard_range_is_bounded_before_creating_chart_points(): void
    {
        config(['reporting.dashboard_max_buckets' => 2]);
        $this->expectException(ValidationException::class);
        app(DashboardService::class)->read($this->user('Administrador'), ['periodo' => 'personalizado', 'fecha_inicio' => '2090-01-01', 'fecha_fin' => '2090-03-01']);
    }
}
