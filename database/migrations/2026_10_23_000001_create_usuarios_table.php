<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE usuarios (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombres             VARCHAR(120) NOT NULL,
    apellidos           VARCHAR(120) NOT NULL,
    correo              VARCHAR(190) NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    estado              ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    fecha_registro      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_usuarios PRIMARY KEY (id),
    CONSTRAINT uq_usuarios_correo UNIQUE (correo)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
