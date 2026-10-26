<?php

use App\Models\AsignacionRol;
use App\Models\EquipamientoTecnologico;
use App\Models\Prestamo;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\LoanService;
use App\Services\ReservationService;
use App\Services\Traceability\EvidenceProcessor;
use App\Services\Traceability\LocalEvidenceService;
use App\Support\TestingDatabaseGuard;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestingDatabaseGuard::check();
$stage = 'setup';
try {
    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    $admin = Usuario::factory()->create();
    (new AsignacionRol)->forceFill(['usuario_id' => $admin->id, 'rol_id' => Rol::where('nombre', 'Administrador')->sole()->id, 'asignado_por_id' => $admin->id, 'fecha_asignacion' => now()])->save();
    $r = Reserva::factory()->create();
    $stage = 'approval';
    app(ReservationService::class)->approve($admin, $r);
    app(ReservationService::class)->cancel($admin, $r);
    $stage = 'loan';
    $p = Prestamo::factory()->create(['equipamiento_id' => EquipamientoTecnologico::factory()->create()->getKey()]);
    app(LoanService::class)->approve($admin, $p);
    app(LoanService::class)->deliver($admin, $p, ['condicion_entrega' => 'Sintético', 'equipo_verificado' => true, 'receptor_verificado' => true]);
    app(LoanService::class)->receive($admin, $p, ['condicion' => 'ADECUADA']);
    $critical = RegistroActividad::where('critica', true)->orderBy('id')->get();
    // CU26 also records the noncritical approval; only D-12's four milestones reach the chain.
    if ($critical->pluck('accion')->all() !== ['RESERVA_APROBADA', 'RESERVA_CANCELADA', 'PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO']
        || RegistroActividad::count() !== 5
        || RegistroActividad::where('accion', 'PRESTAMO_APROBADO')->where('critica', false)->count() !== 1
        || DB::transactionLevel() !== 0) {
        throw new RuntimeException;
    }
    foreach ($critical as $a) {
        $stage = 'local';
        $e = app(LocalEvidenceService::class)->ensure($a->id);
        $stage = 'broadcast';
        app(EvidenceProcessor::class)->register($e->id);
        $stage = 'confirm';
        app(EvidenceProcessor::class)->register($e->id);
        $stage = 'verify';
        if ($e->fresh()->estado->value !== 'REGISTRADA' || app(EvidenceProcessor::class)->verify($e->id) !== 'VALIDO') {
            throw new RuntimeException;
        }
    }
    echo json_encode(['status' => 'passed']);
} catch (Throwable $error) {
    echo json_encode(['status' => 'failed', 'stage' => $stage, 'class' => get_class($error), 'line' => $error->getLine()]);
    exit(1);
} finally {
    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
}
