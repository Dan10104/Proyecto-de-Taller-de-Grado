<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE roles_permisos (
    rol_id              BIGINT UNSIGNED NOT NULL,
    permiso_id          BIGINT UNSIGNED NOT NULL,

    CONSTRAINT pk_roles_permisos PRIMARY KEY (rol_id, permiso_id),

    CONSTRAINT fk_roles_permisos_rol
        FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_roles_permisos_permiso
        FOREIGN KEY (permiso_id) REFERENCES permisos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};
