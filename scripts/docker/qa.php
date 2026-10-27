<?php

use App\Http\RawIotRequest;
use App\Models\Usuario;
use App\Services\LoanService;
use App\Services\ReservationService;
use App\Services\Traceability\TraceabilityCanonicalizer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Synthetic validation only. Stream into `docker compose exec -T web-a php /dev/stdin MODE`.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('deployment.target') !== 'local-container' || config('database.connections.mysql.database') !== 'espacio_u_container' || config('database.connections.mysql.host') !== 'db') {
    fwrite(STDERR, "QA requires the isolated local container database.\n");
    exit(64);
}

function check(bool $condition, string $name): void
{
    if (! $condition) {
        throw new RuntimeException('QA failed: '.$name);
    }
}
try {
    $mode = $argv[1] ?? '';
    $admin = Usuario::where('correo', 'docker-admin@espacio-u.test')->firstOrFail();
    if ($mode === 'setup') {
        check(! DB::table('recursos_institucionales')->where('codigo', 'DOCKER-QA-SPACE')->exists(), 'fixture already exists');
        $floor = DB::table('pisos')->where('numero', 140)->value('id') ?? DB::table('pisos')->insertGetId(['numero' => 140, 'nombre' => 'Piso sintético Docker']);
        $type = DB::table('tipos_espacio')->insertGetId(['codigo' => 'DOCKER-QA-ROOM', 'nombre' => 'Aula Docker']);
        $space = DB::table('recursos_institucionales')->insertGetId(['codigo' => 'DOCKER-QA-SPACE', 'nombre' => 'Espacio sintético Docker']);
        DB::table('espacios_academicos')->insert(['recurso_id' => $space, 'piso_id' => $floor, 'tipo_espacio_id' => $type, 'capacidad' => 30, 'estado' => 'DISPONIBLE']);
        $equipmentType = DB::table('tipos_equipamiento')->insertGetId(['codigo' => 'DOCKER-QA-PROJECTOR', 'nombre' => 'Proyector Docker']);
        $equipment = DB::table('recursos_institucionales')->insertGetId(['codigo' => 'DOCKER-QA-EQUIPMENT', 'nombre' => 'Equipo sintético Docker']);
        DB::table('equipamientos_tecnologicos')->insert(['recurso_id' => $equipment, 'tipo_equipamiento_id' => $equipmentType, 'marca' => 'Sintética', 'modelo' => '=1+1', 'estado' => 'DISPONIBLE']);
        $teacher = new Usuario;
        $teacher->forceFill(['nombres' => 'Docente', 'apellidos' => 'Docker', 'correo' => 'docker-teacher@espacio-u.test', 'password_hash' => $admin->password_hash, 'estado' => 'ACTIVO', 'fecha_registro' => now()])->save();
        DB::table('asignaciones_roles')->insert(['usuario_id' => $teacher->id, 'rol_id' => DB::table('roles')->where('nombre', 'Docente')->value('id'), 'asignado_por_id' => $admin->id, 'fecha_asignacion' => now()]);
        $reservation = app(ReservationService::class)->create($teacher, ['espacio_id' => $space, 'inicio' => now()->addDay()->format('Y-m-d').'T08:00', 'fin' => now()->addDay()->format('Y-m-d').'T09:00', 'proposito' => 'Validación sintética Docker']);
        app(ReservationService::class)->approve($admin, $reservation);
        $loan = app(LoanService::class)->create($teacher, ['equipamiento_id' => $equipment, 'inicio_previsto' => now()->addDay()->format('Y-m-d').'T08:00', 'fin_previsto' => now()->addDay()->format('Y-m-d').'T09:00', 'motivo' => 'Validación sintética Docker']);
        app(LoanService::class)->approve($admin, $loan);
        DB::table('sensores_iot')->insert(['espacio_actual_id' => $space, 'identificador' => 'DOCKER-QA-SENSOR', 'tipo' => 'Sintético', 'estado' => 'HABILITADO']);
        echo json_encode(['space' => $space, 'equipment' => $equipment, 'reservation' => $reservation->id, 'loan' => $loan->id, 'teacher' => $teacher->id]);
    } elseif ($mode === 'runtime') {
        check(posix_getuid() !== 0, 'non-root');
        check(! file_exists('.env') && ! is_dir('blockchain/node_modules/hardhat') && ! is_dir('blockchain/node_modules/zod'), 'runtime separation');
        foreach (['pdo_mysql', 'mbstring', 'zip', 'dom', 'xmlreader', 'pcntl', 'fileinfo', 'openssl'] as $extension) {
            check(extension_loaded($extension), 'extension '.$extension);
        }
        check(is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'runtime write paths');
        check(! is_writable(base_path('app')) && ! is_writable(public_path('index.php')), 'immutable code');
        check(file_exists(base_path('bootstrap/cache/config.php')) && file_exists(base_path('bootstrap/cache/routes-v7.php')), 'runtime caches');
        $jcs = app(TraceabilityCanonicalizer::class);
        check($jcs->serialize(['z' => "\u{2028}", 'a' => [1e20, 1e30, 1e-6, -0.0]]) === '{"a":[100000000000000000000,1e+30,0.000001,0],"z":"'."\u{2028}".'"}', 'JCS numeric golden');
        check($jcs->serialize(['b' => 2, 'a' => 1]) === '{"a":1,"b":2}', 'JCS ordering');
        $connection = DB::connection();
        $denied = false;
        try {
            $connection->statement('CREATE TABLE phase14_forbidden_ddl (id INT)');
        } catch (QueryException) {
            $denied = true;
        }
        check($denied, 'runtime DDL denied');
        check(DB::table('notificaciones')->count() > 0, 'asynchronous notifications');
        check(DB::table('evidencias_trazabilidad')->count() > 0, 'local evidence while blockchain disabled');
        echo json_encode(['ok' => true, 'php' => PHP_VERSION, 'mysql' => DB::selectOne('SELECT VERSION() v')->v, 'uid' => posix_getuid(), 'academic_tables' => 31, 'framework_tables' => 4, 'notifications' => DB::table('notificaciones')->count()]);
    } elseif ($mode === 'iot') {
        $raw = json_encode(['sensor_identifier' => 'DOCKER-QA-SENSOR', 'space_code' => 'DOCKER-QA-SPACE', 'type' => 'PRESENCIA', 'occurred_at' => now()->startOfSecond()->toIso8601String()]);
        $stamp = (string) time();
        $key = hash_hmac('sha256', 'sensor:DOCKER-QA-SENSOR', hex2bin(config('iot.master_secret')), true);
        $signature = hash_hmac('sha256', "POST\n/api/iot/events\nDOCKER-QA-SENSOR\n".$stamp."\n".hash('sha256', $raw), $key);
        // Exercise the real HTTP kernel with the TLS server signal. No external service.
        $request = new RawIotRequest([], [], [], [], [], ['HTTPS' => 'on', 'REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/iot/events', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => 443, 'REMOTE_ADDR' => '127.0.0.1'], $raw);
        $request->headers->replace(['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-IoT-Sensor' => 'DOCKER-QA-SENSOR', 'X-IoT-Timestamp' => $stamp, 'X-IoT-Signature' => $signature]);
        $kernel = app(Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        check(in_array($response->getStatusCode(), [200, 201]), 'HMAC ingestion status '.$response->getStatusCode());
        echo json_encode(['ok' => true, 'status' => $response->getStatusCode(), 'events' => DB::table('eventos_ocupacion')->count()]);
    } elseif ($mode === 'revoke') {
        DB::table('sesiones_acceso')->where('usuario_id', $admin->id)->whereNull('revocada_en')->update(['revocada_en' => now()]);
        echo '{"ok":true}';
    } elseif ($mode === 'disable' || $mode === 'enable') {
        DB::table('usuarios')->where('id', $admin->id)->update(['estado' => $mode === 'disable' ? 'INACTIVO' : 'ACTIVO']);
        echo '{"ok":true}';
    } elseif ($mode === 'deny-dashboard' || $mode === 'restore-dashboard') {
        $role = DB::table('roles')->where('nombre', 'Administrador')->value('id');
        $permission = DB::table('permisos')->where('codigo', 'dashboard.ver')->value('id');
        if ($mode === 'deny-dashboard') {
            DB::table('roles_permisos')->where('rol_id', $role)->where('permiso_id', $permission)->delete();
        } else {
            DB::table('roles_permisos')->insertOrIgnore(['rol_id' => $role, 'permiso_id' => $permission]);
        }
        echo '{"ok":true}';
    } elseif ($mode === 'counts') {
        echo json_encode(['exports' => DB::table('registros_actividad')->where('accion', 'REPORTE_EXPORTADO')->count(), 'jobs' => DB::table('jobs')->count(), 'notifications' => DB::table('notificaciones')->count(), 'events' => DB::table('eventos_ocupacion')->count()]);
    } elseif ($mode === 'orphan') {
        $directory = storage_path('app/private/reports/'.str_repeat('a', 32));
        mkdir($directory, 0700);
        file_put_contents($directory.'/lease', '');
        touch($directory, time() - 3601);
        echo '{"ok":true}';
    } elseif ($mode === 'orphan-check') {
        check(! is_dir(storage_path('app/private/reports/'.str_repeat('a', 32))), 'per-instance orphan cleanup');
        echo '{"ok":true}';
    } else {
        throw new RuntimeException('Unknown QA mode');
    }
} catch (Throwable $error) {
    // Never print framework exceptions: SQL and bindings may contain secrets.
    fwrite(STDERR, json_encode(['ok' => false, 'mode' => $mode ?? '', 'class' => $error::class, 'check' => str_starts_with($error->getMessage(), 'QA failed:') ? $error->getMessage() : 'operation_failed'])."\n");
    exit(1);
}
