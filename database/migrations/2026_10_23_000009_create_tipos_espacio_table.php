<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE tipos_espacio (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(60) NOT NULL,
    nombre              VARCHAR(120) NOT NULL,

    CONSTRAINT pk_tipos_espacio PRIMARY KEY (id),
    CONSTRAINT uq_tipo_espacio_codigo UNIQUE (codigo)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_espacio');
    }
};
