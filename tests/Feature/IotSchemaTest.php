<?php

namespace Tests\Feature;

use App\Models\EventoOcupacion;
use App\Models\SensorIot;
use Illuminate\Support\Facades\DB;
use Tests\SecurityTestCase;

class IotSchemaTest extends SecurityTestCase
{
    public function test_two_tables_match_final_sql_without_extra_fields_constraints_or_enums(): void
    {
        $expected = [
            'sensores_iot' => ['id' => 'bigint unsigned', 'espacio_actual_id' => 'bigint unsigned', 'identificador' => 'varchar(120)', 'tipo' => 'varchar(100)', 'estado' => "enum('HABILITADO','DESHABILITADO')", 'ultima_comunicacion' => 'datetime'],
            'eventos_ocupacion' => ['id' => 'bigint unsigned', 'sensor_id' => 'bigint unsigned', 'espacio_historico_id' => 'bigint unsigned', 'tipo' => "enum('PRESENCIA','OCUPACION')", 'valor' => 'decimal(10,2)', 'fecha_evento' => 'datetime', 'fecha_recepcion' => 'datetime'],
        ];
        foreach ($expected as $table => $columns) {
            $actual = DB::select('SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame($columns, array_column($actual, 'type', 'name'));
            $this->assertSame('InnoDB', DB::selectOne('SELECT ENGINE as engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table])->engine);
        }
        $foreign = DB::select("SELECT UPDATE_RULE as u, DELETE_RULE as d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ('sensores_iot','eventos_ocupacion')");
        $this->assertCount(3, $foreign);
        foreach ($foreign as $f) {
            $this->assertSame('CASCADE', $f->u);
            $this->assertSame('RESTRICT', $f->d);
        }
        $indices = DB::select("SELECT TABLE_NAME as t,INDEX_NAME as i,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('sensores_iot','eventos_ocupacion') GROUP BY TABLE_NAME,INDEX_NAME");
        $this->assertCount(7, $indices);
        $this->assertContains('sensor_id,fecha_evento', array_column($indices, 'c'));
        $this->assertContains('espacio_historico_id,fecha_evento', array_column($indices, 'c'));
        $this->assertSame('CURRENT_TIMESTAMP', strtoupper(DB::selectOne("SELECT COLUMN_DEFAULT as d FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='eventos_ocupacion' AND COLUMN_NAME='fecha_recepcion'")->d));
    }

    public function test_factories_and_relationships_are_synthetic_and_exact(): void
    {
        $s = SensorIot::factory()->create();
        $e = EventoOcupacion::factory()->create(['sensor_id' => $s->id]);
        $this->assertTrue($e->sensor->is($s));
        $this->assertTrue($e->espacioHistorico->is($s->espacioActual));
        $this->assertTrue($s->eventos()->sole()->is($e));
        $this->assertTrue($s->espacioActual->sensoresActuales()->sole()->is($s));
        $this->assertTrue($s->espacioActual->eventosHistoricos()->sole()->is($e));
        $this->assertNull($s->ultima_comunicacion);
        $this->assertNull($e->valor);
    }
}
