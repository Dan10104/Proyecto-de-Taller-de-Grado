<?php

namespace Tests\Feature;

use App\Enums\TipoNotificacion;
use App\Models\Notificacion;
use App\Models\Permiso;
use App\Support\PermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\SecurityTestCase;

#[Group('notifications')]
class NotificationSchemaTest extends SecurityTestCase
{
    public function test_exact_official_columns_defaults_indexes_and_foreign_keys(): void
    {
        $cols = DB::select("SELECT COLUMN_NAME n,COLUMN_TYPE t,IS_NULLABLE z,COLUMN_DEFAULT d,EXTRA e FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notificaciones' ORDER BY ORDINAL_POSITION");
        $this->assertSame(['id' => 'bigint unsigned', 'destinatario_id' => 'bigint unsigned', 'registro_actividad_id' => 'bigint unsigned', 'titulo' => 'varchar(160)', 'mensaje' => 'text', 'tipo' => "enum('SOLICITUD','DECISION','CANCELACION','ENTREGA','DEVOLUCION','VENCIMIENTO','INCIDENCIA','LIBERACION')", 'creada_en' => 'datetime', 'leida_en' => 'datetime'], array_column($cols, 't', 'n'));
        foreach ($cols as $c) {
            $this->assertSame($c->n === 'leida_en' ? 'YES' : 'NO', $c->z);
            $this->assertSame($c->n === 'creada_en' ? 'CURRENT_TIMESTAMP' : null, $c->d === null ? null : strtoupper($c->d));
        }
        $indexes = collect(DB::select('SHOW INDEX FROM notificaciones'))->groupBy('Key_name');
        $this->assertSame(['destinatario_id', 'leida_en'], $indexes['idx_notificacion_destinatario']->pluck('Column_name')->all());
        $this->assertSame(['creada_en'], $indexes['idx_notificacion_fecha']->pluck('Column_name')->all());
        $this->assertSame(['PRIMARY'], $indexes->filter(fn ($i) => $i[0]->Non_unique === 0)->keys()->all());
        $fks = DB::select("SELECT CONSTRAINT_NAME n,UPDATE_RULE u,DELETE_RULE d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='notificaciones'");
        $this->assertCount(2, $fks);
        foreach ($fks as $f) {
            $this->assertSame('CASCADE', $f->u);
            $this->assertSame('RESTRICT', $f->d);
        }
        $this->assertCount(8, TipoNotificacion::cases());
        $this->assertCount(50, PermissionCatalog::DEFINITIONS);
        $this->assertSame(1, Permiso::where('codigo', 'like', 'notificaciones.%')->count());
    }

    public function test_title_length_foreign_restrict_and_historical_model_protection(): void
    {
        $n = Notificacion::factory()->create(['titulo' => str_repeat('á', 160)]);
        $this->assertSame(160, mb_strlen($n->titulo));
        foreach ([fn () => DB::table('notificaciones')->where('id', $n->id)->update(['titulo' => str_repeat('a', 161)]), fn () => DB::table('notificaciones')->where('id', $n->id)->update(['tipo' => 'OTRO']), fn () => DB::table('registros_actividad')->where('id', $n->registro_actividad_id)->delete(), fn () => DB::table('usuarios')->where('id', $n->destinatario_id)->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail('MySQL must reject');
            } catch (QueryException $e) {
                $this->assertContains((int) $e->errorInfo[1], [1265, 1406, 1451]);
            }
        }
        foreach ([fn () => $n->forceFill(['mensaje' => 'changed'])->save(), fn () => $n->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail();
            } catch (\LogicException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $n->refresh();
        $n->forceFill(['leida_en' => now()])->save();
        try {
            $n->forceFill(['leida_en' => null])->save();
            $this->fail();
        } catch (\LogicException) {
            $this->assertNotNull($n->fresh()->leida_en);
        }
        $this->assertTrue($n->destinatario->notificaciones->contains('id', $n->id));
        $this->assertTrue($n->registroActividad->notificaciones->contains('id', $n->id));
    }
}
