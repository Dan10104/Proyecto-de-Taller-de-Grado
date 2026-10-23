<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE caracteristicas_espacio (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(120) NOT NULL,
    descripcion         VARCHAR(255) NULL,

    CONSTRAINT pk_caracteristicas_espacio PRIMARY KEY (id),
    CONSTRAINT uq_caracteristica_nombre UNIQUE (nombre)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('caracteristicas_espacio');
    }
};
