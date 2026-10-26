<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Notifications\NotificationReadService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\NotificationTestCase;

#[Group('notifications')]
class NotificationPerformanceTest extends NotificationTestCase
{
    public function test_scoped_counter_list_and_admin_batch_have_bounded_queries_and_record_explain(): void
    {
        $a = $this->activity('RESERVA_SOLICITADA');
        $u = $a->usuarioAfectado;
        $other = $this->user('Docente');
        $rows = [];
        for ($i = 0; $i < 6000; $i++) {
            $rows[] = ['destinatario_id' => $i < 1000 ? $u->id : $other->id, 'registro_actividad_id' => $a->id, 'titulo' => 'Aviso sintético', 'mensaje' => 'Prueba de volumen sintética.', 'tipo' => 'SOLICITUD', 'creada_en' => now()->toDateTimeString(), 'leida_en' => $i % 2 ? now()->toDateTimeString() : null];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('notificaciones')->insert($chunk);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        $t = hrtime(true);
        $count = app(NotificationReadService::class)->unread($u);
        $counterMs = (hrtime(true) - $t) / 1e6;
        $this->assertSame(500, $count);
        $this->assertCount(1, DB::getQueryLog());
        DB::flushQueryLog();
        $t = hrtime(true);
        $page = Notificacion::where('destinatario_id', $u->id)->orderByDesc('creada_en')->orderByDesc('id')->paginate(15, ['id', 'titulo', 'tipo', 'creada_en', 'leida_en']);
        $listMs = (hrtime(true) - $t) / 1e6;
        $this->assertSame(1000, $page->total());
        $this->assertCount(15, $page);
        $this->assertCount(2, DB::getQueryLog());
        DB::disableQueryLog();
        $planCounter = DB::select('EXPLAIN FORMAT=TRADITIONAL SELECT COUNT(*) FROM notificaciones WHERE destinatario_id=? AND leida_en IS NULL', [$u->id]);
        $planList = DB::select('EXPLAIN FORMAT=TRADITIONAL SELECT id,titulo,tipo,creada_en,leida_en FROM notificaciones WHERE destinatario_id=? ORDER BY creada_en DESC,id DESC LIMIT 15', [$u->id]);
        $this->assertSame('idx_notificacion_destinatario', $planCounter[0]->key);
        $this->assertStringContainsString('idx_notificacion_destinatario', $planList[0]->possible_keys);
        $b = $this->activity('RESERVA_SOLICITADA', $u);
        $role = Rol::where('nombre', 'Administrador')->sole();
        $prototype = Usuario::findOrFail($b->actor_id)->getAttributes();
        unset($prototype['id']);
        for ($i = 0; $i < 200; $i++) {
            $prototype['correo'] = 'performance-'.$i.'@example.test';
            $id = DB::table('usuarios')->insertGetId($prototype);
            DB::table('asignaciones_roles')->insert(['usuario_id' => $id, 'rol_id' => $role->id, 'asignado_por_id' => $u->id, 'fecha_asignacion' => now()]);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        $t = hrtime(true);
        $created = $this->generate($b);
        $batchMs = (hrtime(true) - $t) / 1e6;
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame(202, $created);
        $this->assertLessThanOrEqual(8, $queries);
        $dir = base_path('.local/qa/phase10');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }file_put_contents($dir.'/performance.json', json_encode(['volume' => 6000, 'owner_rows' => 1000, 'unread' => 500, 'counter_ms' => $counterMs, 'counter_queries' => 1, 'list_ms' => $listMs, 'list_queries' => 2, 'batch_recipients' => $created, 'batch_ms' => $batchMs, 'batch_queries' => $queries, 'explain_counter' => $planCounter, 'explain_list' => $planList], JSON_PRETTY_PRINT));
    }
}
