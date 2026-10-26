<?php

namespace Tests\Feature;

use App\Enums\EstadoReserva;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\ReservationTestCase;

class ReservationSchemaTest extends ReservationTestCase
{
    public function test_official_columns_nullability_defaults_keys_and_enum(): void
    {
        $columns = DB::select("SELECT COLUMN_NAME n,COLUMN_TYPE t,IS_NULLABLE z,COLUMN_DEFAULT d,EXTRA e FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='reservas' ORDER BY ORDINAL_POSITION");
        $this->assertSame(['id' => 'bigint unsigned', 'codigo' => 'varchar(60)', 'espacio_id' => 'bigint unsigned', 'solicitante_id' => 'bigint unsigned', 'resuelta_por_id' => 'bigint unsigned', 'cancelada_por_id' => 'bigint unsigned', 'inicio' => 'datetime', 'fin' => 'datetime', 'proposito' => 'varchar(500)', 'solicitada_en' => 'datetime', 'estado' => "enum('PENDIENTE','APROBADA','RECHAZADA','CANCELADA','LIBERADA','FINALIZADA')", 'resuelta_en' => 'datetime', 'motivo_rechazo' => 'varchar(500)', 'cancelada_en' => 'datetime', 'motivo_cancelacion' => 'varchar(500)'], array_column($columns, 't', 'n'));
        foreach ($columns as $c) {
            $this->assertSame(in_array($c->n, ['resuelta_por_id', 'cancelada_por_id', 'resuelta_en', 'motivo_rechazo', 'cancelada_en', 'motivo_cancelacion']) ? 'YES' : 'NO', $c->z);
            $this->assertSame(match ($c->n) {
                'solicitada_en' => 'CURRENT_TIMESTAMP','estado' => 'PENDIENTE',default => null
            }, $c->d);
        }
        $indexes = collect(DB::select('SHOW INDEX FROM reservas'))->groupBy('Key_name');
        foreach (['PRIMARY' => ['id'], 'uq_reservas_codigo' => ['codigo'], 'idx_reserva_espacio_periodo' => ['espacio_id', 'inicio', 'fin'], 'idx_reserva_solicitante' => ['solicitante_id'], 'idx_reserva_estado' => ['estado']] as $key => $cols) {
            $this->assertSame($cols, $indexes[$key]->pluck('Column_name')->all());
            $this->assertSame(in_array($key, ['PRIMARY', 'uq_reservas_codigo']) ? 0 : 1, (int) $indexes[$key][0]->Non_unique);
        }
        $fks = DB::select("SELECT k.COLUMN_NAME n,k.REFERENCED_TABLE_NAME t,k.REFERENCED_COLUMN_NAME c,r.UPDATE_RULE u,r.DELETE_RULE d FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON k.CONSTRAINT_SCHEMA=r.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=r.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='reservas'");
        $this->assertCount(4, $fks);
        foreach ($fks as $fk) {
            $this->assertSame($fk->n === 'espacio_id' ? 'espacios_academicos' : 'usuarios', $fk->t);
            $this->assertSame($fk->n === 'espacio_id' ? 'recurso_id' : 'id', $fk->c);
            $this->assertSame('CASCADE', $fk->u);
            $this->assertSame('RESTRICT', $fk->d);
        }
        $this->assertSame(['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA', 'LIBERADA', 'FINALIZADA'], array_column(EstadoReserva::cases(), 'value'));
        $this->assertTrue(Schema::hasTable('codigos_qr'));
        $this->assertTrue(Schema::hasTable('registros_actividad')); // Authorized Phase 9.
        $this->assertTrue(Schema::hasTable('notificaciones')); // Authorized Phase 10.
        foreach (['lecturas_iot'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
    }

    public function test_mysql_enforces_unique_check_enum_foreign_keys_and_restrict(): void
    {
        $r = $this->pending();
        foreach ([['codigo' => $r->codigo], ['fin' => $r->inicio], ['fin' => $r->inicio->subMinute()], ['estado' => 'ACTIVA'], ['espacio_id' => 999999], ['solicitante_id' => 999999], ['resuelta_por_id' => 999999], ['cancelada_por_id' => 999999]] as $bad) {
            $row = [...$r->getRawOriginal(), ...$bad];
            unset($row['id']);
            if (! isset($bad['codigo'])) {
                $row['codigo'] = 'TEST-'.bin2hex(random_bytes(8));
            }
            try {
                DB::table('reservas')->insert($row);
                $this->fail('La restricción debe impedir la escritura');
            } catch (QueryException $e) {
                $this->assertContains($e->errorInfo[1], [1062, 3819, 1265, 1452]);
            }
        }
        foreach ([$r->espacio, $r->solicitante] as $parent) {
            try {
                $parent->delete();
                $this->fail('No borrar historial');
            } catch (QueryException $e) {
                $this->assertSame(1451, $e->errorInfo[1]);
            }
        }
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_model_relations_casts_guards_and_immutable_code(): void
    {
        $r = $this->pending();
        $this->assertFalse($r->timestamps);
        $this->assertSame(EstadoReserva::PENDIENTE, $r->estado);
        $this->assertInstanceOf(CarbonImmutable::class, $r->inicio);
        $this->assertSame($r->id, $r->espacio->reservas->sole()->id);
        $this->assertNull($r->resueltaPor);
        $this->assertNull($r->canceladaPor);
        $this->assertMatchesRegularExpression('/^RES-[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $r->codigo);
        $this->assertSame(40, strlen($r->codigo));
        foreach (['estado', 'codigo', 'solicitante_id', 'resuelta_por_id', 'cancelada_por_id'] as $field) {
            $this->assertFalse($r->isFillable($field));
        }
        $r->codigo = 'RES-CHANGE';
        $this->expectException(\LogicException::class);
        $r->save();
    }
}
