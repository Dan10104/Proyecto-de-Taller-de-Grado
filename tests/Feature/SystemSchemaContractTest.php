<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\SecurityTestCase;

class SystemSchemaContractTest extends SecurityTestCase
{
    public function test_entire_mysql_schema_matches_frozen_document_dictionary_and_only_eight_approved_exceptions(): void
    {
        $source = file_get_contents(base_path('docs/DATA_MODEL.md'));
        preg_match_all('/^### ([a-z_]+)\R(.*?)(?=^### |\z)/ms', $source, $sections, PREG_SET_ORDER);
        $tables = [];
        $expectedForeign = [];
        $columnCount = 0;
        $restrict = ['codigos_qr.recurso_id', 'codigos_qr.reserva_id', 'codigos_qr.prestamo_id', 'validaciones_qr.reserva_id', 'validaciones_qr.prestamo_id', 'validaciones_qr.entrega_id', 'validaciones_qr.devolucion_id', 'registros_actividad.actor_id'];
        foreach ($sections as [, $table, $body]) {
            preg_match_all('/^\| `([a-z_]+)` \| `((?:BIGINT|INT|VARCHAR|DATETIME|ENUM|BOOLEAN|TINYINT|DECIMAL|TEXT|CHAR)[^`]+)` \|$/m', $body, $columns, PREG_SET_ORDER);
            if (! $columns) {
                continue;
            }
            $tables[] = $table;
            $actual = DB::select('SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS value, EXTRA AS extra FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame(array_column($columns, 1), array_column($actual, 'name'), $table);
            foreach ($columns as $i => [, $name, $definition]) {
                $columnCount++;
                $type = preg_split('/\s+(?:NOT NULL|NULL|DEFAULT|AUTO_INCREMENT)/', $definition)[0];
                $type = str_replace('BOOLEAN', 'tinyint(1)', $type);
                $this->assertSame(strtolower(str_replace(', ', ',', $type)), strtolower($actual[$i]->type), "$table.$name type");
                $this->assertSame(str_contains($definition, 'NOT NULL') ? 'NO' : 'YES', $actual[$i]->nullable, "$table.$name nullable");
                $this->assertSame(str_contains($definition, 'AUTO_INCREMENT'), str_contains($actual[$i]->extra, 'auto_increment'), "$table.$name auto");
                $default = null;
                if (preg_match('/DEFAULT (.+)$/', $definition, $match)) {
                    $default = trim($match[1], "'");
                    $default = match ($default) {
                        'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP', 'TRUE' => '1', 'FALSE' => '0', default => $default
                    };
                }
                $value = $actual[$i]->value;
                if ($value !== null && strtolower($value) === 'current_timestamp()') {
                    $value = 'CURRENT_TIMESTAMP';
                }
                $this->assertSame($default, $value, "$table.$name default");
            }
            preg_match_all('/^\| `([a-z_]+)` \| `([a-z_]+)\.([a-z_]+)` \| [^|]+ \| [^|]+ \| (CASCADE|RESTRICT|SET NULL) \|$/m', $body, $relations, PREG_SET_ORDER);
            foreach ($relations as [, $column, $parent, $key, $delete]) {
                $expectedForeign[$table.'.'.$column] = [$parent, $key, $delete, in_array($table.'.'.$column, $restrict, true) ? 'RESTRICT' : 'CASCADE'];
            }
        }
        $actualTables = DB::table('information_schema.TABLES')->whereRaw('TABLE_SCHEMA=DATABASE()')->whereNotIn('TABLE_NAME', ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs'])->pluck('TABLE_NAME')->all();
        $this->assertCount(31, $tables);
        $this->assertSame(211, $columnCount);
        $this->assertEqualsCanonicalizing($tables, $actualTables);
        $foreign = DB::select('SELECT k.TABLE_NAME AS t,k.COLUMN_NAME AS c,k.REFERENCED_TABLE_NAME AS p,k.REFERENCED_COLUMN_NAME AS k,r.UPDATE_RULE AS u,r.DELETE_RULE AS d,r.CONSTRAINT_NAME AS n FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE()');
        $this->assertCount(63, $foreign);
        $this->assertCount(63, $expectedForeign);
        $restrictedNames = [];
        foreach ($foreign as $fk) {
            $this->assertSame($expectedForeign[$fk->t.'.'.$fk->c], [$fk->p, $fk->k, $fk->d, $fk->u], $fk->n);
            if ($fk->u === 'RESTRICT') {
                $restrictedNames[] = $fk->n;
            }
            // Identifiers originate only in MySQL's own schema metadata, never client input.
            $orphans = DB::table($fk->t.' as c')->whereNotNull('c.'.$fk->c)->whereNotExists(fn ($q) => $q->selectRaw('1')->from($fk->p.' as p')->whereColumn('p.'.$fk->k, 'c.'.$fk->c))->count();
            $this->assertSame(0, $orphans, $fk->n.' orphans');
        }
        $this->assertEqualsCanonicalizing(['fk_qr_recurso', 'fk_qr_reserva', 'fk_qr_prestamo', 'fk_validacion_reserva', 'fk_validacion_prestamo', 'fk_validacion_entrega', 'fk_validacion_devolucion', 'fk_registro_actor'], $restrictedNames);
    }
}
