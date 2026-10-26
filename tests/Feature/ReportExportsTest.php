<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\RegistroActividad;
use App\Services\Reporting\ReportBudget;
use App\Services\Reporting\ReportExportService;
use App\Services\Reporting\ReportGenerationService;
use App\Services\Reporting\ReportQueryService;
use App\Services\Reporting\ReportTemporaryStore;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReportingTestCase;
use ZipArchive;

class ReportExportsTest extends ReportingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        /* PHPUnit retains all loaded test classes; separate-process benchmarks use the 64 MiB production default. */ config(['reporting.memory_bytes' => 256 * 1024 * 1024]);
    }

    #[DataProvider('formats')]
    public function test_successful_file_matches_preview_and_creates_one_noncritical_activity_per_request(string $format): void
    {
        Bus::fake();
        $a = $this->user('Administrador');
        $first = $this->reservation();
        $second = $this->reservation();
        $q = app(ReportQueryService::class);
        $p = $q->preview($a, 'reservas', []);
        $this->assertDatabaseCount('registros_actividad', 0);
        for ($i = 1; $i <= 2; $i++) {
            $response = app(ReportGenerationService::class)->generate($a, 'reservas', [], $format, $p['firma_preview']);
            $path = $response->getFile()->getPathname();
            $this->assertFileExists($path);
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
            $this->assertMatchesRegularExpression('/espacio-u_reservas_20900110-083000\.'.$format.'/', $response->headers->get('Content-Disposition'));
            if ($format === 'pdf') {
                $this->assertSame('%PDF-', file_get_contents($path, false, null, 0, 5));
                $this->assertStringContainsString('%%EOF', file_get_contents($path));
            } else {
                $reader = new Reader;
                $reader->open($path);
                $rows = [];
                $sheets = 0;
                foreach ($reader->getSheetIterator() as $sheet) {
                    $sheets++;
                    $this->assertSame('Reporte', $sheet->getName());
                    foreach ($sheet->getRowIterator() as $row) {
                        $rows[] = $row->toArray();
                    }
                }$reader->close();
                $this->assertSame(1, $sheets);
                $this->assertSame($first->codigo, $rows[count($rows) - 2][0]);
                $this->assertSame($second->codigo, $rows[count($rows) - 1][0]);
                $this->assertInstanceOf(\DateTimeInterface::class, $rows[count($rows) - 1][3]);
                $this->assertSame(array_column($p['columnas'], 'label'), $rows[count($rows) - 3]);
            }
            $activity = RegistroActividad::where('accion', 'REPORTE_EXPORTADO')->latest('id')->firstOrFail();
            $this->assertSame($a->id, $activity->actor_id);
            $this->assertFalse($activity->critica);
            $this->assertNull($activity->proceso_responsable);
            $this->assertSame('EXITO', $activity->resultado->value);
            $this->assertNull($activity->reserva_id);
            $this->assertSame($i, RegistroActividad::where('accion', 'REPORTE_EXPORTADO')->count());
            $response->cleanup();
            $this->assertFileDoesNotExist($path);
        }
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNothingDispatched();
    }

    public static function formats(): array
    {
        return [['pdf'], ['xlsx']];
    }

    #[DataProvider('dangerousText')]
    public function test_xlsx_user_text_is_explicitly_inert_even_with_whitespace_and_control_prefixes(string $value): void
    {
        $e = EquipamientoTecnologico::factory()->create();
        $e->recurso->forceFill(['nombre' => $value])->save();
        $a = $this->user('Administrador');
        $p = $this->report('equipamiento', [], $a);
        $r = app(ReportGenerationService::class)->generate($a, 'equipamiento', [], 'xlsx', $p['firma_preview']);
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($r->getFile()->getPathname()) === true);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertFalse($zip->locateName('xl/vbaProject.bin'));
            $this->assertStringNotContainsString('<f', $xml);
            $document = simplexml_load_string($xml);
            $document->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $row = $document->xpath('//s:sheetData/s:row[last()]/s:c')[1];
            $this->assertSame('inlineStr', (string) $row['t']);
            $zip->close();
            $reader = new Reader;
            $reader->open($r->getFile()->getPathname());
            $last = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $last = $row->toArray();
                }
            }$reader->close();
            $this->assertSame(str_replace(["\r\n", "\r"], "\n", $value), $last[1]);
        } finally {
            $r->cleanup();
        }
    }

    public static function dangerousText(): array
    {
        return [['=1+1'], ['+CMD'], ['-1+2'], ['@SUM(A1:A2)'], ['  =1+1'], ["\t=1+1"], ["\r\n+CMD"], ["\x01@SUM(A1:A2)"], ['<script>alert(1)</script>']];
    }

    public function test_empty_export_and_missing_preview_create_no_activity_or_artifact(): void
    {
        $a = $this->user('Administrador');
        $p = $this->report('reservas', [], $a);
        foreach ([null, $p['firma_preview']] as $fingerprint) {
            try {
                app(ReportGenerationService::class)->generate($a, 'reservas', [], 'pdf', $fingerprint);
                $this->fail('Must reject');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('exportacion', $e->errors());
            }
        }$this->assertDatabaseCount('registros_actividad', 0);
    }

    public function test_changed_filters_or_permissions_require_new_preview(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        EquipamientoTecnologico::factory()->create();
        $p = $this->report('equipamiento', [], $a);
        $this->revoke('equipamientos.gestionar');
        $this->expectException(ValidationException::class);
        app(ReportGenerationService::class)->generate($a, 'equipamiento', [], 'xlsx', $p['firma_preview']);
    }

    public function test_http_preview_export_options_and_arbitrary_payload_rejection(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $this->login($a);
        $this->withCredentials();
        $this->get('/reportes?reporte=reservas')->assertOk();
        $this->assertDatabaseCount('registros_actividad', 0);
        $this->getJson('/reportes/opciones?reporte=reservas&campo=solicitante_id')->assertOk()->assertJsonPath('opciones.0.id', $r->solicitante_id);
        $this->getJson('/reportes/opciones?reporte=reservas&campo=password_hash')->assertUnprocessable();
        $this->postJson('/reportes/exportar', ['reporte' => 'reservas', 'formato' => 'xlsx', 'filas' => [['codigo' => 'FORGED']]])->assertUnprocessable();
        $response = $this->postJson('/reportes/exportar', ['reporte' => 'reservas', 'formato' => 'xlsx']);
        $response->assertOk();
        $this->assertDatabaseCount('registros_actividad', 1);
        $response->baseResponse->cleanup();
    }

    public function test_renderer_failure_never_audits_success(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $this->reservation();
        $p = $this->report('reservas', [], $a);
        $this->mock(ReportExportService::class)->shouldReceive('render')->once()->andThrow(new \RuntimeException('Synthetic render failure'));
        try {
            app(ReportGenerationService::class)->generate($a, 'reservas', [], 'pdf', $p['firma_preview']);
            $this->fail('Must fail');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic render failure', $e->getMessage());
        }$this->assertDatabaseCount('registros_actividad', 0);
    }

    #[DataProvider('limits')]
    public function test_exact_row_limit_is_enforced_before_materializing_and_never_truncates(int $n, bool $allowed): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $base = DB::table('reservas')->where('id', $r->id)->first();
        $template = (array) $base;
        unset($template['id']);
        DB::table('reservas')->where('id', $r->id)->delete();
        for ($offset = 0; $offset < $n; $offset += 500) {
            $batch = [];
            for ($i = $offset; $i < min($offset + 500, $n); $i++) {
                $batch[] = [...$template, 'codigo' => 'LIMIT-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)];
            }DB::table('reservas')->insert($batch);
        }
        $q = app(ReportQueryService::class);
        $prepared = $q->prepare($a, 'reservas', [], true);
        $store = app(ReportTemporaryStore::class);
        $dir = $store->create();
        try {
            $data = $q->materialize($prepared, $a, $dir, new ReportBudget);
            $this->assertTrue($allowed);
            $this->assertSame($n, $data->count);
            $count = 0;
            foreach ($data->rows() as $row) {
                $count++;
            }$this->assertSame($n, $count);
        } catch (ValidationException $e) {
            $this->assertFalse($allowed);
            $this->assertStringContainsString('10000', $e->getMessage());
        } finally {
            $store->remove($dir);
        }$this->assertDatabaseCount('registros_actividad', 0);
    }

    public static function limits(): array
    {
        return [[9999, true], [10000, true], [10001, false]];
    }

    public function test_temporaries_private_cleanup_and_orphan_sweep_keep_active_files(): void
    {
        $store = app(ReportTemporaryStore::class);
        $active = $store->create();
        $this->assertStringNotContainsString(public_path(), $active);
        $old = $store->base().DIRECTORY_SEPARATOR.str_repeat('a', 32);
        mkdir($old);
        file_put_contents($old.'/lease', '');
        file_put_contents($old.'/output.pdf', 'synthetic');
        touch($old, time() - 3600);
        touch($active, time() - 3600);
        $this->assertSame(1, $store->cleanup());
        $this->assertDirectoryDoesNotExist($old);
        $this->assertDirectoryExists($active);
        $store->remove($active);
        $this->assertDirectoryDoesNotExist($active);
    }

    public function test_budget_rejects_memory_and_size_excess(): void
    {
        $store = app(ReportTemporaryStore::class);
        $dir = $store->create();
        $file = $dir.'/output';
        file_put_contents($file, '1234');
        config(['reporting.max_bytes' => 3]);
        try {
            (new ReportBudget)->check($file);
            $this->fail('Oversize');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tamaño', $e->getMessage());
        }config(['reporting.memory_bytes' => 1]);
        try {
            (new ReportBudget)->check();
            $this->fail('Memory');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('memoria', $e->getMessage());
        } finally {
            $store->remove($dir);
        }
    }
}
