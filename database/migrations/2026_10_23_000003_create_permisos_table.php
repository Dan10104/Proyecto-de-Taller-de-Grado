<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE permisos (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(100) NOT NULL,
    nombre              VARCHAR(120) NOT NULL,
    descripcion         VARCHAR(255) NULL,

    CONSTRAINT pk_permisos PRIMARY KEY (id),
    CONSTRAINT uq_permisos_codigo UNIQUE (codigo)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
