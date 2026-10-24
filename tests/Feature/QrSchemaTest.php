<?php

namespace Tests\Feature;

use App\Models\ValidacionQr;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\QrTestCase;

class QrSchemaTest extends QrTestCase
{
    public function test_actual_mysql_metadata_matches_academic_sql_with_seven_authorized_update_exceptions(): void
    {
        // Independent expected metadata from the final academic dictionary; the seven RESTRICT actions are explicitly authorized.
        $columns = [
            'codigos_qr' => ['id', 'recurso_id', 'reserva_id', 'prestamo_id', 'token_hash', 'generada_en', 'vence_en', 'activo'],
            'validaciones_qr' => ['id', 'codigo_qr_id', 'recurso_esperado_id', 'reserva_id', 'prestamo_id', 'entrega_id', 'devolucion_id', 'validado_por_id', 'fecha_hora', 'resultado', 'tipo_operacion', 'motivo_rechazo', 'hash_leido'],
        ];
        $nullable = ['recurso_id', 'reserva_id', 'prestamo_id', 'vence_en', 'codigo_qr_id', 'entrega_id', 'devolucion_id', 'motivo_rechazo'];
        $types = ['token_hash' => 'varchar(255)', 'generada_en' => 'datetime', 'vence_en' => 'datetime', 'activo' => 'tinyint(1)', 'fecha_hora' => 'datetime', 'resultado' => "enum('VALIDA','INVALIDA')", 'tipo_operacion' => "enum('USO_ESPACIO','ENTREGA','DEVOLUCION')", 'motivo_rechazo' => 'varchar(500)', 'hash_leido' => 'varchar(255)'];
        foreach ($columns as $table => $names) {
            $actual = DB::select('SELECT COLUMN_NAME n,COLUMN_TYPE t,IS_NULLABLE z,COLUMN_DEFAULT d,EXTRA e FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame($names, array_column($actual, 'n'));
            foreach ($actual as $c) {
                $this->assertSame($types[$c->n] ?? 'bigint unsigned', $c->t);
                $this->assertSame(in_array($c->n, $nullable, true) ? 'YES' : 'NO', $c->z);
                $this->assertSame(in_array($c->n, ['generada_en', 'fecha_hora'], true) ? 'CURRENT_TIMESTAMP' : ($c->n === 'activo' ? '1' : null), $c->d);
                $this->assertSame($c->n === 'id', str_contains($c->e, 'auto_increment'));
            }
        }
        $fks = [
            'fk_qr_recurso' => ['codigos_qr', 'recurso_id', 'recursos_institucionales', 'RESTRICT'], 'fk_qr_reserva' => ['codigos_qr', 'reserva_id', 'reservas', 'RESTRICT'], 'fk_qr_prestamo' => ['codigos_qr', 'prestamo_id', 'prestamos', 'RESTRICT'],
            'fk_validacion_codigo' => ['validaciones_qr', 'codigo_qr_id', 'codigos_qr', 'CASCADE'], 'fk_validacion_recurso_esperado' => ['validaciones_qr', 'recurso_esperado_id', 'recursos_institucionales', 'CASCADE'], 'fk_validacion_reserva' => ['validaciones_qr', 'reserva_id', 'reservas', 'RESTRICT'], 'fk_validacion_prestamo' => ['validaciones_qr', 'prestamo_id', 'prestamos', 'RESTRICT'], 'fk_validacion_entrega' => ['validaciones_qr', 'entrega_id', 'entregas_prestamo', 'RESTRICT'], 'fk_validacion_devolucion' => ['validaciones_qr', 'devolucion_id', 'devoluciones_prestamo', 'RESTRICT'], 'fk_validacion_usuario' => ['validaciones_qr', 'validado_por_id', 'usuarios', 'CASCADE'],
        ];
        $actual = DB::select("SELECT k.CONSTRAINT_NAME n,k.TABLE_NAME t,k.COLUMN_NAME c,k.REFERENCED_TABLE_NAME r,k.REFERENCED_COLUMN_NAME p,x.UPDATE_RULE u,x.DELETE_RULE d FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS x ON x.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND x.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME IN ('codigos_qr','validaciones_qr')");
        $this->assertCount(10, $actual);
        foreach ($actual as $fk) {
            $this->assertSame($fks[$fk->n], [$fk->t, $fk->c, $fk->r, $fk->u]);
            $this->assertSame('id', $fk->p);
            $this->assertSame('RESTRICT', $fk->d);
        }
        $indexes = ['codigos_qr' => ['PRIMARY' => ['id', true], 'uq_codigo_qr_token_hash' => ['token_hash', true], 'idx_qr_recurso' => ['recurso_id', false], 'idx_qr_reserva' => ['reserva_id', false], 'idx_qr_prestamo' => ['prestamo_id', false], 'idx_qr_activo' => ['activo', false]], 'validaciones_qr' => ['PRIMARY' => ['id', true], 'uq_validacion_entrega' => ['entrega_id', true], 'uq_validacion_devolucion' => ['devolucion_id', true], 'idx_validacion_codigo' => ['codigo_qr_id', false], 'idx_validacion_reserva' => ['reserva_id', false], 'idx_validacion_prestamo' => ['prestamo_id', false], 'idx_validacion_usuario' => ['validado_por_id', false], 'idx_validacion_fecha' => ['fecha_hora', false]]];
        foreach ($indexes as $table => $spec) {
            $rows = collect(DB::select('SHOW INDEX FROM '.$table))->keyBy('Key_name');
            foreach ($spec as $name => [$column,$unique]) {
                $this->assertTrue($rows->has($name));
                $this->assertSame($column, $rows[$name]->Column_name);
                $this->assertSame($unique ? 0 : 1, (int) $rows[$name]->Non_unique);
            }
        }
        $checks = DB::select("SELECT CONSTRAINT_NAME n,ENFORCED e FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('codigos_qr','validaciones_qr') AND CONSTRAINT_TYPE='CHECK' ORDER BY CONSTRAINT_NAME");
        $this->assertSame(['chk_qr_objetivo', 'chk_qr_vigencia', 'chk_validacion_contexto', 'chk_validacion_entrega_devolucion'], array_column($checks, 'n'));
        foreach ($checks as $c) {
            $this->assertSame('YES', $c->e);
        }
    }

    private function rejected(callable $operation): void
    {
        try {
            $operation();
            $this->fail('MySQL debe rechazar esta escritura.');
        } catch (QueryException $e) {
            $this->assertContains($e->errorInfo[1], [3819, 1265, 1451, 1452, 1062, 1048]);
        }
    }

    public function test_xor_vigencia_enums_unique_fk_and_context_enforced_by_mysql(): void
    {
        $r = $this->reservation();
        $p = $this->loan();
        $t = $this->token();
        $c = $this->code($t, ['recurso_id' => $r->espacio_id]);
        foreach ([['recurso_id' => null], ['reserva_id' => $r->id], ['prestamo_id' => $p->id], ['vence_en' => $c->generada_en->subSecond()], ['recurso_id' => 999999]] as $data) {
            $this->rejected(fn () => DB::table('codigos_qr')->where('id', $c->id)->update($data));
        }
        $this->rejected(fn () => $this->code($t, ['reserva_id' => $r->id]));
        $c->forceFill(['vence_en' => $c->generada_en])->save();
        $this->assertTrue($c->fresh()->vence_en->equalTo($c->generada_en));
        $v = ValidacionQr::factory()->create(['reserva_id' => $r->id, 'codigo_qr_id' => $c->id]);
        foreach ([['reserva_id' => null], ['prestamo_id' => $p->id], ['tipo_operacion' => 'ENTREGA'], ['resultado' => 'OTRO'], ['hash_leido' => null], ['codigo_qr_id' => 999999], ['recurso_esperado_id' => 999999]] as $data) {
            $this->rejected(fn () => DB::table('validaciones_qr')->where('id', $v->id)->update($data));
        }
        $this->rejected(fn () => $c->delete());
        $this->rejected(fn () => $r->delete());
        $this->rejected(fn () => DB::table('recursos_institucionales')->where('id', $r->espacio_id)->update(['id' => 999998]));
        $this->rejected(fn () => DB::table('reservas')->where('id', $r->id)->update(['id' => 999997]));
        DB::table('codigos_qr')->where('id', $c->id)->update(['id' => 999996]);
        $this->assertSame(999996, $v->fresh()->codigo_qr_id);
    }

    public function test_hito_unique_and_exclusive_links_and_seven_restrict_parents(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'ADECUADA']);
        $p->refresh();
        $code = $this->code($this->token(), ['prestamo_id' => $p->id]);
        $base = ['reserva_id' => null, 'prestamo_id' => $p->id, 'recurso_esperado_id' => $p->equipamiento_id, 'validado_por_id' => $a->id, 'codigo_qr_id' => $code->id, 'resultado' => 'VALIDA', 'motivo_rechazo' => null];
        $d = ValidacionQr::factory()->create($base + ['tipo_operacion' => 'ENTREGA', 'entrega_id' => $p->entrega->id]);
        $v = ValidacionQr::factory()->create($base + ['tipo_operacion' => 'DEVOLUCION', 'devolucion_id' => $p->devolucion->id]);
        foreach ([$d, $v] as $validation) {
            $this->rejected(fn () => DB::table('validaciones_qr')->insert(array_diff_key($validation->getRawOriginal(), ['id' => true])));
        }
        $this->rejected(fn () => DB::table('validaciones_qr')->where('id', $d->id)->update(['devolucion_id' => $p->devolucion->id]));
        $this->rejected(fn () => DB::table('validaciones_qr')->where('id', $v->id)->update(['entrega_id' => $p->entrega->id]));
        foreach (['prestamos' => $p->id, 'entregas_prestamo' => $p->entrega->id, 'devoluciones_prestamo' => $p->devolucion->id] as $table => $id) {
            $this->rejected(fn () => DB::table($table)->where('id', $id)->update(['id' => 999999]));
        }
        $this->assertSame($p->id, $code->prestamo->id);
        $this->assertSame($p->id, $d->prestamo->id);
        $this->assertSame($code->id, $d->codigoQr->id);
        $this->assertSame($p->entrega->id, $d->entrega->id);
        $this->assertSame($p->devolucion->id, $v->devolucion->id);
        $this->assertSame($a->id, $v->validadoPor->id);
        $this->assertSame($p->equipamiento_id, $v->recursoEsperado->id);
        $this->assertCount(2, $code->validaciones);
        foreach ([$code, $d, $v] as $model) {
            $this->assertFalse($model->timestamps);
            $this->assertFalse($model->isFillable('id'));
            $this->assertArrayNotHasKey('token_hash', $model->toArray());
            $this->assertArrayNotHasKey('hash_leido', $model->toArray());
        }
        $this->assertInstanceOf(CarbonImmutable::class, $v->fecha_hora);
    }
}
