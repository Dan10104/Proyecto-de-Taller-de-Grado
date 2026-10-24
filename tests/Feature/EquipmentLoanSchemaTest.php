<?php

namespace Tests\Feature;

use App\Enums\CondicionDevolucion;
use App\Enums\EstadoDevolucion;
use App\Enums\EstadoEquipamiento;
use App\Enums\EstadoIncidencia;
use App\Enums\EstadoPrestamo;
use App\Enums\TipoIncidencia;
use App\Models\DevolucionPrestamo;
use App\Models\EntregaPrestamo;
use App\Models\EquipamientoTecnologico;
use App\Models\IncidenciaMantenimiento;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\LoanTestCase;

class EquipmentLoanSchemaTest extends LoanTestCase
{
    public function test_six_tables_match_final_document_columns_nullability_defaults_keys_and_foreign_actions(): void
    {
        // Expected metadata transcribed from the final academic SQL, not from runtime migrations.
        $spec = json_decode(<<<'JSON'
{"tipos_equipamiento":{"columns":{"id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":true},"codigo":{"type":"varchar(60)","nullable":"NO","default":null,"auto":false},"nombre":{"type":"varchar(120)","nullable":"NO","default":null,"auto":false}},"fks":{},"indexes":{"PRIMARY":{"cols":["id"],"unique":true},"uq_tipo_equipamiento_codigo":{"cols":["codigo"],"unique":true}}},"equipamientos_tecnologicos":{"columns":{"recurso_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"tipo_equipamiento_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"espacio_base_id":{"type":"bigint unsigned","nullable":"YES","default":null,"auto":false},"descripcion":{"type":"text","nullable":"YES","default":null,"auto":false},"marca":{"type":"varchar(120)","nullable":"YES","default":null,"auto":false},"modelo":{"type":"varchar(120)","nullable":"YES","default":null,"auto":false},"numero_serie":{"type":"varchar(160)","nullable":"YES","default":null,"auto":false},"estado":{"type":"enum('DISPONIBLE','PRESTADO','EN_MANTENIMIENTO','BAJA')","nullable":"NO","default":"DISPONIBLE","auto":false},"referencia_ubicacion":{"type":"varchar(255)","nullable":"YES","default":null,"auto":false}},"fks":{"fk_equipo_recurso":{"col":"recurso_id","table":"recursos_institucionales","key":"id","update":"CASCADE","delete":"CASCADE"},"fk_equipo_tipo":{"col":"tipo_equipamiento_id","table":"tipos_equipamiento","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_equipo_espacio_base":{"col":"espacio_base_id","table":"espacios_academicos","key":"recurso_id","update":"CASCADE","delete":"RESTRICT"}},"indexes":{"PRIMARY":{"cols":["recurso_id"],"unique":true},"uq_equipamiento_numero_serie":{"cols":["numero_serie"],"unique":true},"idx_equipo_tipo":{"cols":["tipo_equipamiento_id"],"unique":false},"idx_equipo_estado":{"cols":["estado"],"unique":false},"idx_equipo_espacio_base":{"cols":["espacio_base_id"],"unique":false}}},"prestamos":{"columns":{"id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":true},"codigo":{"type":"varchar(60)","nullable":"NO","default":null,"auto":false},"equipamiento_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"solicitante_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"resuelto_por_id":{"type":"bigint unsigned","nullable":"YES","default":null,"auto":false},"inicio_previsto":{"type":"datetime","nullable":"NO","default":null,"auto":false},"fin_previsto":{"type":"datetime","nullable":"NO","default":null,"auto":false},"motivo":{"type":"varchar(500)","nullable":"NO","default":null,"auto":false},"solicitado_en":{"type":"datetime","nullable":"NO","default":"CURRENT_TIMESTAMP","auto":false},"estado":{"type":"enum('PENDIENTE','APROBADO','RECHAZADO','ENTREGADO','DEVUELTO')","nullable":"NO","default":"PENDIENTE","auto":false},"resuelto_en":{"type":"datetime","nullable":"YES","default":null,"auto":false},"motivo_rechazo":{"type":"varchar(500)","nullable":"YES","default":null,"auto":false}},"fks":{"fk_prestamo_equipo":{"col":"equipamiento_id","table":"equipamientos_tecnologicos","key":"recurso_id","update":"CASCADE","delete":"RESTRICT"},"fk_prestamo_solicitante":{"col":"solicitante_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_prestamo_resuelto_por":{"col":"resuelto_por_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"}},"indexes":{"PRIMARY":{"cols":["id"],"unique":true},"uq_prestamos_codigo":{"cols":["codigo"],"unique":true},"idx_prestamo_equipo_periodo":{"cols":["equipamiento_id","inicio_previsto","fin_previsto"],"unique":false},"idx_prestamo_solicitante":{"cols":["solicitante_id"],"unique":false},"idx_prestamo_estado":{"cols":["estado"],"unique":false}}},"entregas_prestamo":{"columns":{"id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":true},"prestamo_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"entregado_por_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"receptor_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"fecha_hora":{"type":"datetime","nullable":"NO","default":null,"auto":false},"condicion_entrega":{"type":"varchar(255)","nullable":"NO","default":null,"auto":false},"observacion":{"type":"varchar(500)","nullable":"YES","default":null,"auto":false},"recepcion_confirmada_en":{"type":"datetime","nullable":"YES","default":null,"auto":false}},"fks":{"fk_entrega_prestamo":{"col":"prestamo_id","table":"prestamos","key":"id","update":"CASCADE","delete":"CASCADE"},"fk_entrega_entregado_por":{"col":"entregado_por_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_entrega_receptor":{"col":"receptor_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"}},"indexes":{"PRIMARY":{"cols":["id"],"unique":true},"uq_entrega_prestamo":{"cols":["prestamo_id"],"unique":true}}},"devoluciones_prestamo":{"columns":{"id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":true},"prestamo_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"recibido_por_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"confirmado_por_id":{"type":"bigint unsigned","nullable":"YES","default":null,"auto":false},"registrada_en":{"type":"datetime","nullable":"NO","default":null,"auto":false},"confirmada_en":{"type":"datetime","nullable":"YES","default":null,"auto":false},"condicion":{"type":"enum('ADECUADA','CON_INCIDENCIA','NO_DETERMINABLE')","nullable":"NO","default":null,"auto":false},"descripcion_condicion":{"type":"varchar(500)","nullable":"YES","default":null,"auto":false},"observacion":{"type":"varchar(500)","nullable":"YES","default":null,"auto":false},"estado":{"type":"enum('PENDIENTE_REVISION','CONFIRMADA')","nullable":"NO","default":"PENDIENTE_REVISION","auto":false}},"fks":{"fk_devolucion_prestamo":{"col":"prestamo_id","table":"prestamos","key":"id","update":"CASCADE","delete":"CASCADE"},"fk_devolucion_recibido_por":{"col":"recibido_por_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_devolucion_confirmado_por":{"col":"confirmado_por_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"}},"indexes":{"PRIMARY":{"cols":["id"],"unique":true},"uq_devolucion_prestamo":{"cols":["prestamo_id"],"unique":true}}},"incidencias_mantenimiento":{"columns":{"id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":true},"recurso_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"reportado_por_id":{"type":"bigint unsigned","nullable":"NO","default":null,"auto":false},"cerrado_por_id":{"type":"bigint unsigned","nullable":"YES","default":null,"auto":false},"devolucion_id":{"type":"bigint unsigned","nullable":"YES","default":null,"auto":false},"tipo":{"type":"enum('INCIDENCIA','MANTENIMIENTO')","nullable":"NO","default":null,"auto":false},"descripcion":{"type":"text","nullable":"NO","default":null,"auto":false},"inicio":{"type":"datetime","nullable":"NO","default":null,"auto":false},"fin_previsto":{"type":"datetime","nullable":"YES","default":null,"auto":false},"cerrada_en":{"type":"datetime","nullable":"YES","default":null,"auto":false},"solucion":{"type":"text","nullable":"YES","default":null,"auto":false},"estado":{"type":"enum('ABIERTA','CERRADA')","nullable":"NO","default":"ABIERTA","auto":false}},"fks":{"fk_incidencia_recurso":{"col":"recurso_id","table":"recursos_institucionales","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_incidencia_reportado_por":{"col":"reportado_por_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_incidencia_cerrado_por":{"col":"cerrado_por_id","table":"usuarios","key":"id","update":"CASCADE","delete":"RESTRICT"},"fk_incidencia_devolucion":{"col":"devolucion_id","table":"devoluciones_prestamo","key":"id","update":"CASCADE","delete":"RESTRICT"}},"indexes":{"PRIMARY":{"cols":["id"],"unique":true},"idx_incidencia_recurso":{"cols":["recurso_id"],"unique":false},"idx_incidencia_estado":{"cols":["estado"],"unique":false}}}}
JSON, true, flags: JSON_THROW_ON_ERROR);
        foreach ($spec as $table => $definition) {
            $columns = DB::select('SELECT COLUMN_NAME n,COLUMN_TYPE t,IS_NULLABLE z,COLUMN_DEFAULT d,EXTRA e FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame(array_keys($definition['columns']), array_column($columns, 'n'), $table);
            foreach ($columns as $c) {
                $expected = $definition['columns'][$c->n];
                $this->assertSame($expected['type'], $c->t, $table.'.'.$c->n);
                $this->assertSame($expected['nullable'], $c->z);
                $this->assertSame($expected['default'], $c->d);
                $this->assertSame($expected['auto'], str_contains($c->e, 'auto_increment'));
            }
            $indexes = collect(DB::select('SHOW INDEX FROM '.$table))->groupBy('Key_name');
            foreach ($definition['indexes'] as $name => $expected) {
                $this->assertTrue($indexes->has($name), $name);
                $this->assertSame($expected['cols'], $indexes[$name]->pluck('Column_name')->all());
                $this->assertSame($expected['unique'] ? 0 : 1, (int) $indexes[$name][0]->Non_unique);
            }
            $fks = DB::select('SELECT k.CONSTRAINT_NAME n,k.COLUMN_NAME c,k.REFERENCED_TABLE_NAME t,k.REFERENCED_COLUMN_NAME k,r.UPDATE_RULE u,r.DELETE_RULE d FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON k.CONSTRAINT_SCHEMA=r.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=r.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=?', [$table]);
            $this->assertCount(count($definition['fks']), $fks);
            foreach ($fks as $fk) {
                $e = $definition['fks'][$fk->n];
                $this->assertSame([$e['col'], $e['table'], $e['key'], $e['update'], $e['delete']], [$fk->c, $fk->t, $fk->k, $fk->u, $fk->d]);
            }
        }
        foreach (['codigos_qr', 'validaciones_qr'] as $table) {
            $this->assertTrue(Schema::hasTable($table)); // Materialized by the authorized Phase 6.
        }
        foreach (['notificaciones', 'registros_actividad', 'lecturas_iot', 'evidencias_blockchain'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
    }

    public function test_models_enum_casts_and_relations_respect_shared_resource_and_hitos(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Daño sintético']);
        $p->refresh();
        $e = $p->equipamiento;
        $r = $p->devolucion;
        $i = $r->incidencias->sole();
        $this->assertSame($e->getKey(), $e->recurso->equipamiento->getKey());
        $this->assertSame($e->getKey(), $e->tipo->equipamientos->sole()->getKey());
        $this->assertSame($p->id, $e->prestamos->sole()->id);
        $this->assertSame($p->id, $p->entrega->prestamo->id);
        $this->assertSame($p->id, $r->prestamo->id);
        $this->assertSame($r->id, $i->devolucion->id);
        $this->assertSame($e->getKey(), $i->recurso->id);
        $this->assertSame($i->id, $e->recurso->incidencias->sole()->id);
        $this->assertSame(EstadoPrestamo::DEVUELTO, $p->estado);
        $this->assertSame(EstadoEquipamiento::EN_MANTENIMIENTO, $e->estado);
        $this->assertSame(EstadoDevolucion::CONFIRMADA, $r->estado);
        $this->assertSame(CondicionDevolucion::CON_INCIDENCIA, $r->condicion);
        $this->assertSame(TipoIncidencia::INCIDENCIA, $i->tipo);
        $this->assertSame(EstadoIncidencia::ABIERTA, $i->estado);
        foreach ([$p, $e, $r, $i, $p->entrega, $e->tipo] as $model) {
            $this->assertFalse($model->timestamps);
            $this->assertFalse($model->isFillable('id'));
            $this->assertFalse($model->isFillable('estado'));
        }
        $this->assertInstanceOf(CarbonImmutable::class, $p->inicio_previsto);
        $this->assertInstanceOf(CarbonImmutable::class, $p->entrega->fecha_hora);
    }

    public function test_mysql_checks_unique_enum_fk_restrict_and_official_cascades(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Incidencia']);
        $p->refresh();
        $i = IncidenciaMantenimiento::sole();
        $invalid = [
            fn () => DB::table('prestamos')->where('id', $p->id)->update(['fin_previsto' => $p->inicio_previsto]),
            fn () => DB::table('prestamos')->where('id', $p->id)->update(['estado' => 'VENCIDO']),
            fn () => DB::table('equipamientos_tecnologicos')->where('recurso_id', $p->equipamiento_id)->update(['tipo_equipamiento_id' => 999999]),
            fn () => DB::table('incidencias_mantenimiento')->where('id', $i->id)->update(['fin_previsto' => $i->inicio->subSecond()]),
            fn () => DB::table('incidencias_mantenimiento')->where('id', $i->id)->update(['cerrada_en' => $i->inicio->subSecond()]),
            fn () => DB::table('entregas_prestamo')->insert(array_diff_key($p->entrega->getRawOriginal(), ['id' => true])),
            fn () => DB::table('devoluciones_prestamo')->insert(array_diff_key($p->devolucion->getRawOriginal(), ['id' => true])),
            fn () => $p->equipamiento->delete(), fn () => $p->devolucion->delete(), fn () => $a->delete(),
        ];
        foreach ($invalid as $operation) {
            try {
                $operation();
                $this->fail('MySQL debe rechazar la operación');
            } catch (QueryException $e) {
                $this->assertContains($e->errorInfo[1], [3819, 1265, 1451, 1452, 1062]);
            }
        }
        DB::table('incidencias_mantenimiento')->where('id', $i->id)->update(['fin_previsto' => $i->inicio]);
        $this->assertTrue($i->fresh()->fin_previsto->equalTo($i->inicio));
        $e = EquipamientoTecnologico::factory()->create();
        $e->recurso->delete();
        $this->assertFalse(EquipamientoTecnologico::whereKey($e->getKey())->exists());
        $other = $this->delivered($a);
        $this->loans()->receive($a, $other, ['condicion' => 'ADECUADA']);
        $other->delete();
        $this->assertNull(EntregaPrestamo::where('prestamo_id', $other->id)->first());
        $this->assertNull(DevolucionPrestamo::where('prestamo_id', $other->id)->first());
    }
}
