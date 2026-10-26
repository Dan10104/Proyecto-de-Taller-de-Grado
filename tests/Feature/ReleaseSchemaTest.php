<?php

namespace Tests\Feature;

use App\Models\EventoOcupacion;
use App\Models\ValidacionQr;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\ReleaseTestCase;

class ReleaseSchemaTest extends ReleaseTestCase
{
    public function test_exact_sql_columns_foreign_keys_indices_defaults_and_engines(): void
    {
        $tables = [
            'evaluaciones_liberacion' => ['id' => 'bigint unsigned', 'reserva_id' => 'bigint unsigned', 'politica_id' => 'bigint unsigned', 'fecha_hora' => 'datetime', 'resultado' => "enum('MANTENER_POR_USO','NO_DETERMINABLE','ALERTAR','LIBERAR')", 'motivo' => 'varchar(500)'],
            'evaluaciones_eventos_ocupacion' => ['evaluacion_id' => 'bigint unsigned', 'evento_id' => 'bigint unsigned'],
            'evaluaciones_validaciones_qr' => ['evaluacion_id' => 'bigint unsigned', 'validacion_id' => 'bigint unsigned'],
        ];
        foreach ($tables as $table => $expected) {
            $columns = DB::select('SELECT COLUMN_NAME as name,COLUMN_TYPE as type,IS_NULLABLE as nullable,COLUMN_DEFAULT as def,EXTRA as extra FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame($expected, array_column($columns, 'type', 'name'));
            foreach ($columns as $c) {
                $this->assertSame($c->name === 'motivo' ? 'YES' : 'NO', $c->nullable);
                $this->assertSame($c->name === 'id', str_contains($c->extra, 'auto_increment'));
                $this->assertSame($c->name === 'fecha_hora' ? 'CURRENT_TIMESTAMP' : null, $c->def === null ? null : strtoupper($c->def));
            }
            $this->assertSame('InnoDB', DB::selectOne('SELECT ENGINE as engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table])->engine);
        }
        $fks = DB::select("SELECT CONSTRAINT_NAME as name,TABLE_NAME as t,REFERENCED_TABLE_NAME as target,UPDATE_RULE as u,DELETE_RULE as d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'evaluaciones_%'");
        $expected = ['fk_eval_reserva' => ['reservas', 'RESTRICT'], 'fk_eval_politica' => ['politicas_liberacion', 'RESTRICT'], 'fk_eval_evento_evaluacion' => ['evaluaciones_liberacion', 'CASCADE'], 'fk_eval_evento_evento' => ['eventos_ocupacion', 'RESTRICT'], 'fk_eval_valid_evaluacion' => ['evaluaciones_liberacion', 'CASCADE'], 'fk_eval_valid_validacion' => ['validaciones_qr', 'RESTRICT']];
        $this->assertCount(6, $fks);
        foreach ($fks as $fk) {
            $this->assertSame('CASCADE', $fk->u);
            $this->assertSame($expected[$fk->name], [$fk->target, $fk->d]);
        }
        foreach (['evaluaciones_liberacion' => 'id', 'evaluaciones_eventos_ocupacion' => 'evaluacion_id,evento_id', 'evaluaciones_validaciones_qr' => 'evaluacion_id,validacion_id'] as $table => $pk) {
            $indices = DB::select('SELECT INDEX_NAME as name,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as cols FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME', [$table]);
            $map = array_column($indices, 'cols', 'name');
            $this->assertSame($pk, $map['PRIMARY']);
            if ($table === 'evaluaciones_liberacion') {
                $this->assertSame(['PRIMARY' => 'id', 'idx_eval_reserva_fecha' => 'reserva_id,fecha_hora', 'idx_eval_politica' => 'politica_id'], ['PRIMARY' => $map['PRIMARY'], 'idx_eval_reserva_fecha' => $map['idx_eval_reserva_fecha'], 'idx_eval_politica' => $map['idx_eval_politica']]);
            }
        }
        $this->assertSame(31, DB::table('information_schema.TABLES')->whereRaw('TABLE_SCHEMA=DATABASE()')->where('TABLE_NAME', '!=', 'migrations')->count());
        $this->assertSame(211, DB::table('information_schema.COLUMNS')->whereRaw('TABLE_SCHEMA=DATABASE()')->where('TABLE_NAME', '!=', 'migrations')->count());
    }

    public function test_database_constraints_and_many_to_many_relationships_preserve_history(): void
    {
        $r = $this->reservation();
        $q = $this->qr($r, ['resultado' => 'INVALIDA']);
        $event = $this->event($this->sensor($r));
        $e = $this->evaluate($r);
        $this->assertTrue($e->reserva->is($r));
        $this->assertTrue($r->evaluaciones()->sole()->is($e));
        $this->assertTrue($e->politica->espacios()->sole()->is($r->espacio));
        $this->assertTrue($event->evaluaciones()->sole()->is($e));
        $this->assertTrue($q->evaluaciones()->sole()->is($e));
        $expectDbFailure = function (callable $fn) {
            try {
                $fn();
                $this->fail('MySQL debe rechazar la escritura.');
            } catch (QueryException $error) {
                $this->assertContains((int) $error->errorInfo[1], [1062, 1265, 1406, 1451, 1452]);
            }
        };
        $expectDbFailure(fn () => $e->eventos()->attach($event->id));
        $expectDbFailure(fn () => $e->validaciones()->attach($q->id));
        $expectDbFailure(fn () => $e->eventos()->attach(999999));
        $expectDbFailure(fn () => $e->validaciones()->attach(999999));
        $expectDbFailure(fn () => DB::table('evaluaciones_liberacion')->where('id', $e->id)->update(['resultado' => 'OTRO']));
        $expectDbFailure(fn () => DB::table('evaluaciones_liberacion')->where('id', $e->id)->update(['motivo' => str_repeat('x', 501)]));
        foreach (['reservas' => $r->id, 'politicas_liberacion' => $e->politica_id, 'eventos_ocupacion' => $event->id, 'validaciones_qr' => $q->id] as $table => $id) {
            $expectDbFailure(fn () => DB::table($table)->where('id', $id)->delete());
        }
        DB::table('evaluaciones_liberacion')->where('id', $e->id)->update(['motivo' => null]);
        $this->assertNull($e->fresh()->motivo); // Physical nullable, application always writes a reason.
        foreach (['update', 'delete'] as $action) {
            try {
                $action === 'update' ? $e->forceFill(['motivo' => 'Cambio'])->save() : $e->delete();
                $this->fail('Historia inmutable.');
            } catch (\LogicException) {
                $this->assertTrue(true);
            }
        }
        DB::table('evaluaciones_liberacion')->where('id', $e->id)->update(['id' => 900]);
        $this->assertSame(900, $event->evaluaciones()->sole()->id);
        $this->assertSame(900, $q->evaluaciones()->sole()->id);
        DB::table('evaluaciones_liberacion')->where('id', 900)->delete(); // SQL action only; no application deletion path.
        $this->assertDatabaseCount('evaluaciones_eventos_ocupacion', 0);
        $this->assertDatabaseCount('evaluaciones_validaciones_qr', 0);
        $this->assertSame(1, EventoOcupacion::count());
        $this->assertSame(1, ValidacionQr::count());
    }

    public function test_only_three_new_migrations_rollback_and_reapply_on_testing(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 3, '--force' => true])->assertSuccessful(); // Phase 10 and Phase 9, then Phase 8.
        $this->assertFalse(Schema::hasTable('notificaciones'));
        $this->artisan('migrate:rollback', ['--step' => 3, '--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('politicas_liberacion'));
        $this->assertTrue(Schema::hasTable('eventos_ocupacion'));
        $this->assertFalse(Schema::hasTable('evaluaciones_liberacion'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('evaluaciones_liberacion'));
        $this->assertTrue(Schema::hasTable('evaluaciones_validaciones_qr'));
        $this->assertTrue(Schema::hasTable('evaluaciones_eventos_ocupacion'));
    }
}
