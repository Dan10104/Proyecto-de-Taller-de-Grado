<?php

use App\Exceptions\IotException;
use App\Exceptions\ReleaseDeferredException;
use App\Models\Reserva;
use App\Services\IotRateLimiter;
use App\Services\Notifications\NotificationGenerationService;
use App\Services\ReleaseEvaluationService;
use App\Services\Traceability\EthereumSepoliaDriver;
use App\Services\Traceability\ProcessEnvironment;
use App\Services\Traceability\TraceabilityLock;
use App\Services\Traceability\TraceabilityUnavailable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('deployment.target') !== 'local-container' || config('database.connections.mysql.database') !== 'espacio_u_container') {
    exit(64);
}
$mode = $argv[1] ?? '';
$key = $argv[2] ?? 'phase14';
try {
    if ($mode === 'cache-hold' || $mode === 'cache-try') {
        $lock = Cache::store('database')->lock('phase14:'.$key, 30);
        $acquired = $lock->get();
        echo ($acquired ? 'ACQUIRED' : 'BUSY')."\n";
        flush();
        if ($acquired) {
            try {
                if ($mode === 'cache-hold') {
                    sleep(3);
                }
            } finally {
                $lock->release();
            }
        }
    } elseif ($mode === 'wallet-hold' || $mode === 'wallet-try') {
        try {
            app(TraceabilityLock::class)->run('wallet:11155111:'.hash('sha256', $key), function () use ($mode) {
                echo "ACQUIRED\n";
                flush();
                if ($mode === 'wallet-hold') {
                    sleep(3);
                }
            });
        } catch (TraceabilityUnavailable) {
            echo "BUSY\n";
        }
    } elseif ($mode === 'quota') {
        try {
            app(IotRateLimiter::class)->consume('phase14:'.$key, 10);
            echo "ACCEPTED\n";
        } catch (IotException $error) {
            echo $error->status === 429 ? "LIMITED\n" : "FAILED\n";
        }
    } elseif ($mode === 'prepare') {
        $activity = DB::table('registros_actividad')->where('accion', 'RESERVA_APROBADA')->orderBy('id')->value('id');
        DB::table('notificaciones')->where('registro_actividad_id', $activity)->delete();
        $policy = DB::table('politicas_liberacion')->insertGetId(['nombre' => 'Docker concurrent '.$key, 'version' => 1, 'tolerancia_min' => 0, 'requiere_qr' => true, 'usa_iot' => false, 'modo' => 'ALERTA', 'activa' => true]);
        DB::table('espacios_academicos')->where('recurso_id', 1)->update(['politica_liberacion_id' => $policy]);
        $reservation = new Reserva;
        $reservation->forceFill(['espacio_id' => 1, 'solicitante_id' => 2, 'inicio' => now()->subMinutes(2), 'fin' => now()->addMinutes(30), 'proposito' => 'Synthetic concurrency', 'estado' => 'APROBADA', 'solicitada_en' => now()->subMinutes(5), 'resuelta_por_id' => 1, 'resuelta_en' => now()->subMinutes(4)])->save();
        echo json_encode(['activity' => $activity, 'reservation' => $reservation->id]);
    } elseif ($mode === 'notify') {
        echo json_encode(['created' => app(NotificationGenerationService::class)->generate((int) $key)]);
    } elseif ($mode === 'release') {
        try {
            $result = app(ReleaseEvaluationService::class)->evaluate((int) $key);
            echo $result ? "CREATED\n" : "UNCHANGED\n";
        } catch (ReleaseDeferredException) {
            echo "DEFERRED\n";
        }
    } elseif ($mode === 'verify') {
        echo json_encode(['notifications' => DB::table('notificaciones')->where('registro_actividad_id', (int) $key)->count(), 'evaluations' => DB::table('evaluaciones_liberacion')->where('reserva_id', (int) $argv[3])->count()]);
    } elseif ($mode === 'bridge') {
        $server = new Process([config('blockchain.node'), '-e', 'require("http").createServer((q,s)=>{let b="";q.on("data",x=>b+=x);q.on("end",()=>{let j=JSON.parse(b);s.setHeader("Content-Type","application/json");s.end(JSON.stringify({jsonrpc:"2.0",id:j.id,result:"0xaa36a7"}));});}).listen(18545,"127.0.0.1",()=>console.log("READY"));'], base_path(), ProcessEnvironment::isolated());
        try {
            $server->start();
            if (! $server->waitUntil(fn ($type, $output) => str_contains($output, 'READY'))) {
                throw new RuntimeException;
            }
            config(['blockchain.enabled' => true, 'blockchain.rpc_url' => 'http://127.0.0.1:18545']);
            if (app(EthereumSepoliaDriver::class)->chainId() !== 11155111) {
                throw new RuntimeException;
            }
            echo "LOCAL_BRIDGE_OK\n";
        } finally {
            $server->stop(1);
        }
    } else {
        exit(64);
    }
} catch (Throwable $error) {
    fwrite(STDERR, json_encode(['mode' => $mode, 'error_class' => $error::class])."\n");
    exit(1);
}
