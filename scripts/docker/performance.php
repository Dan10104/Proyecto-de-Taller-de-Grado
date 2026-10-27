<?php

use App\Models\Usuario;
use App\Services\Reporting\ReportGenerationService;
use App\Services\Reporting\ReportQueryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('deployment.target') !== 'local-container' || config('database.connections.mysql.database') !== 'espacio_u_container' || config('database.connections.mysql.host') !== 'db') {
    exit(64);
}
$format = $argv[1] ?? '';
if (! in_array($format, ['pdf', 'xlsx'], true)) {
    exit(64);
}
DB::beginTransaction();
try {
    $actor = Usuario::where('correo', 'docker-admin@espacio-u.test')->firstOrFail();
    $template = (array) DB::table('reservas')->where('id', 1)->first();
    unset($template['id']);
    for ($offset = 1; $offset < 10000; $offset += 500) {
        $rows = [];
        for ($i = $offset; $i < min(10000, $offset + 500); $i++) {
            $rows[] = [...$template, 'codigo' => 'BENCH-F14-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT)];
        }
        DB::table('reservas')->insert($rows);
    }
    $day = substr($template['inicio'], 0, 10);
    $filters = ['periodo' => 'personalizado', 'fecha_inicio' => $day, 'fecha_fin' => $day];
    $queries = app(ReportQueryService::class);
    $start = hrtime(true);
    $preview = $queries->preview($actor, 'reservas', $filters);
    $previewMs = (hrtime(true) - $start) / 1e6;
    if ($preview['total'] !== 10000) {
        throw new RuntimeException('Unexpected synthetic row count');
    }
    $samples = [];
    $peaks = [];
    for ($i = 0; $i < 3; $i++) {
        memory_reset_peak_usage();
        $start = hrtime(true);
        $response = app(ReportGenerationService::class)->generate($actor, 'reservas', $filters, $format, $preview['firma_preview']);
        $samples[] = (hrtime(true) - $start) / 1e6;
        $peaks[] = memory_get_peak_usage(true) / 1048576;
        $bytes = $response->getFile()->getSize();
        $response->cleanup();
        unset($response);
        gc_collect_cycles();
    }
    echo json_encode(['rows' => 10000, 'format' => $format, 'preview_ms' => $previewMs, 'samples_ms' => $samples, 'mean_ms' => array_sum($samples) / 3, 'peak_mib' => max($peaks), 'bytes' => $bytes, 'local_only' => true, 'fixture_rollback' => true]);
} catch (Throwable $error) {
    fwrite(STDERR, json_encode(['ok' => false, 'class' => $error::class])."\n");
    exit(1);
} finally {
    DB::rollBack();
}
