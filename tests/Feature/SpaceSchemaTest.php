<?php

namespace Tests\Feature;

use App\Models\CaracteristicaEspacio;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\RecursoInstitucional;
use App\Models\TipoEspacio;
use App\Services\SpaceManagementService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SpaceTestCase;

class SpaceSchemaTest extends SpaceTestCase
{
    public function test_eight_tables_match_official_columns_types_keys_defaults_and_foreign_keys(): void
    {
        $expected = [
            'pisos' => ['id' => 'bigint unsigned', 'numero' => 'int', 'nombre' => 'varchar(100)'],
            'recursos_institucionales' => ['id' => 'bigint unsigned', 'codigo' => 'varchar(60)', 'nombre' => 'varchar(160)'],
            'tipos_espacio' => ['id' => 'bigint unsigned', 'codigo' => 'varchar(60)', 'nombre' => 'varchar(120)'],
            'caracteristicas_espacio' => ['id' => 'bigint unsigned', 'nombre' => 'varchar(120)', 'descripcion' => 'varchar(255)'],
            'politicas_liberacion' => ['id' => 'bigint unsigned', 'nombre' => 'varchar(120)', 'version' => 'int', 'tolerancia_min' => 'int', 'requiere_qr' => 'tinyint(1)', 'usa_iot' => 'tinyint(1)', 'max_antiguedad_lectura_min' => 'int', 'modo' => "enum('ALERTA','AUTOMATICA')", 'activa' => 'tinyint(1)'],
            'espacios_academicos' => ['recurso_id' => 'bigint unsigned', 'piso_id' => 'bigint unsigned', 'tipo_espacio_id' => 'bigint unsigned', 'politica_liberacion_id' => 'bigint unsigned', 'capacidad' => 'int', 'referencia_ubicacion' => 'varchar(255)', 'estado' => "enum('DISPONIBLE','BLOQUEADO','EN_MANTENIMIENTO')"],
            'espacios_caracteristicas' => ['espacio_id' => 'bigint unsigned', 'caracteristica_id' => 'bigint unsigned'],
            'bloqueos_espacio' => ['id' => 'bigint unsigned', 'espacio_id' => 'bigint unsigned', 'responsable_id' => 'bigint unsigned', 'inicio' => 'datetime', 'fin' => 'datetime', 'motivo' => 'varchar(500)', 'activo' => 'tinyint(1)'],
        ];
        foreach ($expected as $table => $columns) {
            $actual = DB::select('SELECT COLUMN_NAME AS name,COLUMN_TYPE AS type,IS_NULLABLE AS nullable,COLUMN_DEFAULT AS def,EXTRA AS extra,COLUMN_KEY AS k FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame($columns, array_column($actual, 'type', 'name'));
            foreach ($actual as $col) {
                $nullable = in_array($table.'.'.$col->name, ['caracteristicas_espacio.descripcion', 'politicas_liberacion.max_antiguedad_lectura_min', 'espacios_academicos.politica_liberacion_id', 'espacios_academicos.referencia_ubicacion']);
                $this->assertSame($nullable ? 'YES' : 'NO', $col->nullable);
                $defaults = ['politicas_liberacion.tolerancia_min' => '0', 'politicas_liberacion.requiere_qr' => '0', 'politicas_liberacion.usa_iot' => '0', 'politicas_liberacion.activa' => '1', 'espacios_academicos.estado' => 'DISPONIBLE', 'bloqueos_espacio.activo' => '1'];
                $this->assertSame($defaults[$table.'.'.$col->name] ?? null, $col->def);
                $this->assertSame(($col->name === 'id' || $table === 'espacios_caracteristicas' || ($table === 'espacios_academicos' && $col->name === 'recurso_id')) ? 'PRI' : $col->k, $col->k);
                $this->assertSame($col->name === 'id' ? 'auto_increment' : '', $col->extra);
            }
        }
        $space = EspacioAcademico::factory()->create();
        $this->assertSame($space->recurso_id, $space->recurso->id);
        $this->assertFalse($space->getIncrementing());
        $this->assertNull($space->politica_liberacion_id);
        $keys = DB::select('SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=? ORDER BY ORDINAL_POSITION', ['espacios_caracteristicas', 'PRIMARY']);
        $this->assertSame(['espacio_id', 'caracteristica_id'], array_column($keys, 'name'));
        $fk = DB::select('SELECT CONSTRAINT_NAME AS name,UPDATE_RULE AS u,DELETE_RULE AS d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()');
        $actions = ['fk_espacio_recurso' => 'CASCADE', 'fk_espacio_piso' => 'RESTRICT', 'fk_espacio_tipo' => 'RESTRICT', 'fk_espacio_politica' => 'RESTRICT', 'fk_esp_car_espacio' => 'CASCADE', 'fk_esp_car_caracteristica' => 'RESTRICT', 'fk_bloqueo_espacio' => 'RESTRICT', 'fk_bloqueo_responsable' => 'RESTRICT'];
        foreach ($actions as $name => $delete) {
            $row = collect($fk)->firstWhere('name', $name);
            $this->assertNotNull($row);
            $this->assertSame('CASCADE', $row->u);
            $this->assertSame($delete, $row->d);
        }
        $this->assertCount(54, $fk);
        $checks = DB::select('SELECT CONSTRAINT_NAME AS name FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()');
        $this->assertEqualsCanonicalizing(['chk_asigrol_fechas', 'chk_sesion_expiracion', 'chk_politica_tolerancia', 'chk_politica_antiguedad', 'chk_espacio_capacidad', 'chk_bloqueo_fechas', 'chk_reserva_fechas', 'chk_prestamo_fechas', 'chk_incidencia_fin_previsto', 'chk_incidencia_cierre', 'chk_qr_objetivo', 'chk_qr_vigencia', 'chk_validacion_contexto', 'chk_validacion_entrega_devolucion'], array_column($checks, 'name'));
        $this->assertTrue(Schema::hasTable('reservas'));
        $this->assertSame(0, DB::table('politicas_liberacion')->count());
    }

    public static function uniqueCases(): array
    {
        return [[Piso::class, 'numero', 123], [RecursoInstitucional::class, 'codigo', 'UNICO-TEST'], [TipoEspacio::class, 'codigo', 'TIPO-UNICO'], [CaracteristicaEspacio::class, 'nombre', 'Característica única sintética']];
    }

    #[DataProvider('uniqueCases')]
    public function test_catalog_identity_is_unique_in_mysql(string $model, string $field, mixed $value): void
    {
        $model::factory()->create([$field => $value]);
        $this->expectException(QueryException::class);
        $model::factory()->create([$field => $value]);
    }

    public static function invalidCapacities(): array
    {
        return [[0], [-1]];
    }

    #[DataProvider('invalidCapacities')]
    public function test_capacity_check_is_enforced_by_mysql(int $capacity): void
    {
        $this->expectException(QueryException::class);
        EspacioAcademico::factory()->create(['capacidad' => $capacity]);
    }

    public function test_failure_in_subclass_rolls_back_parent(): void
    {
        $actor = $this->user('Administrador');
        $data = $this->spaceData(['capacidad' => 0]);
        try {
            app(SpaceManagementService::class)->save($actor, null, $data);
            $this->fail('SQL must reject capacity');
        } catch (QueryException $e) {
            $this->assertSame(3819, $e->errorInfo[1]);
        }
        $this->assertDatabaseCount('recursos_institucionales', 0);
        $this->assertDatabaseCount('espacios_academicos', 0);
    }

    public function test_failure_in_association_rolls_back_both_resource_and_subclass(): void
    {
        $actor = $this->user('Administrador');
        $data = $this->spaceData(['caracteristicas' => [999999]]);
        try {
            app(SpaceManagementService::class)->save($actor, null, $data);
            $this->fail('FK must reject association');
        } catch (QueryException $e) {
            $this->assertSame(1452, $e->errorInfo[1]);
        }
        $this->assertDatabaseCount('recursos_institucionales', 0);
        $this->assertDatabaseCount('espacios_academicos', 0);
    }

    public function test_fk_cascade_and_restrict_preserve_contract(): void
    {
        $space = EspacioAcademico::factory()->create();
        $feature = CaracteristicaEspacio::factory()->create();
        $space->caracteristicas()->attach($feature);
        foreach ([fn () => $space->piso->delete(), fn () => $space->tipo->delete(), fn () => $feature->delete(), fn () => $space->caracteristicas()->attach($feature)] as $invalid) {
            try {
                $invalid();
                $this->fail('Constraint must reject operation');
            } catch (QueryException $e) {
                $this->assertContains($e->errorInfo[1], [1451, 1062]);
            }
        }
        $space->recurso->delete();
        $this->assertDatabaseCount('espacios_academicos', 0);
        $this->assertDatabaseCount('espacios_caracteristicas', 0);
        $this->assertDatabaseCount('caracteristicas_espacio', 1);
        $space = EspacioAcademico::factory()->create();
        $block = $this->blockFor($space, '2027-01-10 08:00', '2027-01-10 09:00');
        foreach ([fn () => $space->recurso->delete(), fn () => $block->responsable->delete()] as $invalid) {
            try {
                $invalid();
                $this->fail('History FK must restrict');
            } catch (QueryException $e) {
                $this->assertSame(1451, $e->errorInfo[1]);
            }
        }
    }

    public static function invalidPeriods(): array
    {
        return [['2027-01-10 08:00'], ['2027-01-10 07:00']];
    }

    #[DataProvider('invalidPeriods')]
    public function test_mysql_rejects_block_end_not_after_start(string $end): void
    {
        $space = EspacioAcademico::factory()->create();
        $this->expectException(QueryException::class);
        $this->blockFor($space, '2027-01-10 08:00', $end);
    }

    public function test_policy_is_only_a_structural_nullable_dependency_with_its_official_constraints(): void
    {
        $data = ['nombre' => 'Política sintética estructural', 'version' => 1, 'modo' => 'ALERTA'];
        $id = DB::table('politicas_liberacion')->insertGetId($data);
        $space = EspacioAcademico::factory()->create(['politica_liberacion_id' => $id]);
        $this->assertSame($id, $space->politica->id);
        foreach ([fn () => DB::table('politicas_liberacion')->insert($data), fn () => DB::table('politicas_liberacion')->where('id', $id)->update(['tolerancia_min' => -1]), fn () => DB::table('politicas_liberacion')->where('id', $id)->update(['max_antiguedad_lectura_min' => -1]), fn () => DB::table('politicas_liberacion')->where('id', $id)->delete()] as $invalid) {
            try {
                $invalid();
                $this->fail('Official constraint must reject operation');
            } catch (QueryException $e) {
                $this->assertContains($e->errorInfo[1], [1062, 3819, 1451]);
            }
        }
        $actor = $this->user('Administrador');
        $data = $this->spaceData(['codigo' => $space->recurso->codigo]);
        app(SpaceManagementService::class)->save($actor, $space, $data);
        $this->assertSame($id, $space->fresh()->politica_liberacion_id);
    }

    public function test_failed_edit_rolls_back_resource_and_feature_changes(): void
    {
        $actor = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        $feature = CaracteristicaEspacio::factory()->create();
        $space->caracteristicas()->attach($feature);
        $previous = $space->recurso->nombre;
        $data = $this->spaceData(['codigo' => $space->recurso->codigo, 'nombre' => 'Cambio que debe revertirse', 'caracteristicas' => [999999]]);
        try {
            app(SpaceManagementService::class)->save($actor, $space, $data);
            $this->fail('Invalid FK must abort');
        } catch (QueryException $e) {
            $this->assertSame(1452, $e->errorInfo[1]);
        }
        $this->assertSame($previous, $space->recurso->fresh()->nombre);
        $this->assertSame([$feature->id], $space->caracteristicas()->pluck('caracteristicas_espacio.id')->all());
    }
}
