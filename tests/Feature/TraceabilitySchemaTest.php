<?php

namespace Tests\Feature;

use App\Models\RegistroActividad;
use App\Services\Traceability\LocalEvidenceService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\ReservationTestCase;

#[Group('traceability')]
class TraceabilitySchemaTest extends ReservationTestCase
{
    public function test_exact_official_columns_nullability_defaults_indexes_foreign_actions_and_check(): void
    {
        $spec = [
            'registros_actividad' => ['id' => 'bigint unsigned', 'actor_id' => 'bigint unsigned', 'usuario_afectado_id' => 'bigint unsigned', 'recurso_id' => 'bigint unsigned', 'reserva_id' => 'bigint unsigned', 'prestamo_id' => 'bigint unsigned', 'incidencia_id' => 'bigint unsigned', 'accion' => 'varchar(160)', 'fecha_hora' => 'datetime', 'resultado' => "enum('EXITO','RECHAZO','ERROR')", 'descripcion' => 'text', 'proceso_responsable' => 'varchar(160)', 'critica' => 'tinyint(1)'],
            'evidencias_trazabilidad' => ['id' => 'bigint unsigned', 'registro_actividad_id' => 'bigint unsigned', 'algoritmo_hash' => 'varchar(50)', 'hash' => 'varchar(255)', 'version_canonica' => 'varchar(60)', 'referencia_externa' => 'varchar(255)', 'registrada_localmente_en' => 'datetime', 'registrada_externamente_en' => 'datetime', 'estado' => "enum('PENDIENTE','REGISTRADA')", 'ultima_verificacion' => 'datetime', 'resultado_verificacion' => "enum('VALIDO','INCONSISTENTE','PENDIENTE','NO_VERIFICABLE')"],
        ];
        $nullable = ['actor_id', 'usuario_afectado_id', 'recurso_id', 'reserva_id', 'prestamo_id', 'incidencia_id', 'descripcion', 'proceso_responsable', 'referencia_externa', 'registrada_externamente_en', 'ultima_verificacion'];
        foreach ($spec as $table => $expected) {
            $cols = DB::select('SELECT COLUMN_NAME n,COLUMN_TYPE t,IS_NULLABLE z,COLUMN_DEFAULT d,EXTRA e FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame($expected, array_column($cols, 't', 'n'));
            foreach ($cols as $c) {
                $this->assertSame(in_array($c->n, $nullable, true) ? 'YES' : 'NO', $c->z);
                $this->assertSame($c->n === 'id', str_contains($c->e, 'auto_increment'));
                $default = match ($c->n) {
                    'fecha_hora', 'registrada_localmente_en' => 'CURRENT_TIMESTAMP', 'estado', 'resultado_verificacion' => 'PENDIENTE', 'critica' => '0', default => null
                };
                $this->assertSame($default, $c->d === null ? null : strtoupper($c->d));
            }
            $this->assertSame('InnoDB', DB::selectOne('SELECT ENGINE e FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table])->e);
        }
        $fks = DB::select("SELECT CONSTRAINT_NAME n,UPDATE_RULE u,DELETE_RULE d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ('registros_actividad','evidencias_trazabilidad')");
        $this->assertCount(7, $fks);
        foreach ($fks as $fk) {
            $this->assertSame($fk->n === 'fk_registro_actor' ? 'RESTRICT' : 'CASCADE', $fk->u);
            $this->assertSame('RESTRICT', $fk->d);
        }
        $indexes = collect(DB::select('SHOW INDEX FROM evidencias_trazabilidad'))->keyBy('Key_name');
        foreach (['uq_evidencia_registro' => 'registro_actividad_id', 'uq_evidencia_hash' => 'hash'] as $key => $column) {
            $this->assertSame($column, $indexes[$key]->Column_name);
            $this->assertSame(0, $indexes[$key]->Non_unique);
        }
        $this->assertTrue($indexes->has('idx_evidencia_estado'));
        $this->assertTrue($indexes->has('idx_evidencia_referencia'));
        $this->assertSame(1, DB::table('information_schema.TABLE_CONSTRAINTS')->whereRaw('CONSTRAINT_SCHEMA=DATABASE()')->where('CONSTRAINT_NAME', 'chk_registro_responsable')->count());
        $this->assertSame(31, DB::table('information_schema.TABLES')->whereRaw('TABLE_SCHEMA=DATABASE()')->whereNotIn('TABLE_NAME', ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs'])->count());
        $this->assertSame(211, DB::table('information_schema.COLUMNS')->whereRaw('TABLE_SCHEMA=DATABASE()')->whereNotIn('TABLE_NAME', ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs'])->count());
        $this->assertSame(63, DB::table('information_schema.REFERENTIAL_CONSTRAINTS')->whereRaw('CONSTRAINT_SCHEMA=DATABASE()')->count());
    }

    public function test_constraints_unique_check_foreign_keys_and_immutable_models(): void
    {
        $this->service()->approve($this->user('Administrador'), $this->pending());
        $a = RegistroActividad::sole();
        $e = app(LocalEvidenceService::class)->ensure($a->id);
        foreach ([fn () => DB::table('registros_actividad')->where('id', $a->id)->update(['actor_id' => null, 'proceso_responsable' => null]),
            fn () => DB::table('evidencias_trazabilidad')->insert(array_diff_key($e->getAttributes(), ['id' => true])),
            fn () => DB::table('registros_actividad')->where('id', $a->id)->delete(),
            fn () => DB::table('usuarios')->where('id', $a->actor_id)->update(['id' => 999999]),
            fn () => DB::table('evidencias_trazabilidad')->where('id', $e->id)->update(['resultado_verificacion' => null])] as $attempt) {
            try {
                $attempt();
                $this->fail('MySQL debe rechazar la alteración.');
            } catch (QueryException $error) {
                $this->assertContains((int) $error->errorInfo[1], [1048, 1062, 1451, 3819]);
            }
        }
        foreach ([fn () => $a->forceFill(['accion' => 'OTRA'])->save(), fn () => $e->forceFill(['hash' => str_repeat('a', 64)])->save(), fn () => $e->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail('El modelo debe proteger la identidad.');
            } catch (\LogicException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
    }
}
