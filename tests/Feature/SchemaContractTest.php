<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\SecurityTestCase;

class SchemaContractTest extends SecurityTestCase
{
    public function test_six_tables_have_only_the_official_columns_and_types(): void
    {
        $expected = [
            'usuarios' => ['id' => 'bigint unsigned', 'nombres' => 'varchar(120)', 'apellidos' => 'varchar(120)', 'correo' => 'varchar(190)', 'password_hash' => 'varchar(255)', 'estado' => "enum('ACTIVO','INACTIVO')", 'fecha_registro' => 'datetime'],
            'roles' => ['id' => 'bigint unsigned', 'nombre' => 'varchar(100)', 'descripcion' => 'varchar(255)'],
            'permisos' => ['id' => 'bigint unsigned', 'codigo' => 'varchar(100)', 'nombre' => 'varchar(120)', 'descripcion' => 'varchar(255)'],
            'roles_permisos' => ['rol_id' => 'bigint unsigned', 'permiso_id' => 'bigint unsigned'],
            'asignaciones_roles' => ['id' => 'bigint unsigned', 'usuario_id' => 'bigint unsigned', 'rol_id' => 'bigint unsigned', 'asignado_por_id' => 'bigint unsigned', 'retirado_por_id' => 'bigint unsigned', 'fecha_asignacion' => 'datetime', 'fecha_retiro' => 'datetime'],
            'sesiones_acceso' => ['id' => 'bigint unsigned', 'usuario_id' => 'bigint unsigned', 'token_hash' => 'varchar(255)', 'creada_en' => 'datetime', 'expira_en' => 'datetime', 'revocada_en' => 'datetime'],
        ];
        $tables = DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()');
        $names = array_column($tables, 'name');
        sort($names);
        $allowed = [...array_keys($expected), 'migrations', 'pisos', 'recursos_institucionales', 'tipos_espacio', 'caracteristicas_espacio', 'politicas_liberacion', 'espacios_academicos', 'espacios_caracteristicas', 'bloqueos_espacio'];
        sort($allowed);
        $this->assertSame($allowed, $names);
        foreach ($expected as $table => $columns) {
            $actual = DB::select('SELECT COLUMN_NAME AS name,COLUMN_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
            $this->assertSame($columns, array_column($actual, 'type', 'name'));
        }
        $fk = DB::select('SELECT UPDATE_RULE AS u,DELETE_RULE AS d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ("usuarios","roles","permisos","roles_permisos","asignaciones_roles","sesiones_acceso")');
        $this->assertCount(7, $fk);
        $this->assertCount(2, array_filter($fk, fn ($r) => $r->d === 'CASCADE'));
        $this->assertCount(5, array_filter($fk, fn ($r) => $r->d === 'RESTRICT'));
        foreach ($fk as $row) {
            $this->assertSame('CASCADE', $row->u);
        }
        $checks = DB::select('SELECT CONSTRAINT_NAME AS name FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME IN ("chk_asigrol_fechas","chk_sesion_expiracion")');
        $this->assertEqualsCanonicalizing(['chk_asigrol_fechas', 'chk_sesion_expiracion'], array_column($checks, 'name'));
    }

    public function test_database_rejects_invalid_session_expiration(): void
    {
        $user = $this->user();
        $this->expectException(QueryException::class);
        DB::table('sesiones_acceso')->insert(['usuario_id' => $user->id, 'token_hash' => hash('sha256', random_bytes(20)), 'creada_en' => now(), 'expira_en' => now()->subMinute()]);
    }

    public function test_database_rejects_retirement_before_assignment(): void
    {
        $user = $this->user('Docente');
        $this->expectException(QueryException::class);
        DB::table('asignaciones_roles')->where('usuario_id', $user->id)->update(['fecha_retiro' => now()->subDay()]);
    }
}
