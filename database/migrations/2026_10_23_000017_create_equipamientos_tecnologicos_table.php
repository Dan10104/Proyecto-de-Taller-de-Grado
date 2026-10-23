<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE equipamientos_tecnologicos (
    recurso_id              BIGINT UNSIGNED NOT NULL,
    tipo_equipamiento_id    BIGINT UNSIGNED NOT NULL,
    espacio_base_id         BIGINT UNSIGNED NULL,
    descripcion             TEXT NULL,
    marca                   VARCHAR(120) NULL,
    modelo                  VARCHAR(120) NULL,
    numero_serie            VARCHAR(160) NULL,
    estado                  ENUM('DISPONIBLE','PRESTADO','EN_MANTENIMIENTO','BAJA')
                            NOT NULL DEFAULT 'DISPONIBLE',
    referencia_ubicacion    VARCHAR(255) NULL,

    CONSTRAINT pk_equipamientos_tecnologicos PRIMARY KEY (recurso_id),
    CONSTRAINT uq_equipamiento_numero_serie UNIQUE (numero_serie),

    CONSTRAINT fk_equipo_recurso
        FOREIGN KEY (recurso_id) REFERENCES recursos_institucionales(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_equipo_tipo
        FOREIGN KEY (tipo_equipamiento_id) REFERENCES tipos_equipamiento(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    -- 0..1 EspacioAcademico como ubicacion base.
    CONSTRAINT fk_equipo_espacio_base
        FOREIGN KEY (espacio_base_id) REFERENCES espacios_academicos(recurso_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_equipo_tipo (tipo_equipamiento_id),
    INDEX idx_equipo_estado (estado),
    INDEX idx_equipo_espacio_base (espacio_base_id)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('equipamientos_tecnologicos');
    }
};
