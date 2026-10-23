<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE espacios_academicos (
    recurso_id              BIGINT UNSIGNED NOT NULL,
    piso_id                 BIGINT UNSIGNED NOT NULL,
    tipo_espacio_id         BIGINT UNSIGNED NOT NULL,
    politica_liberacion_id  BIGINT UNSIGNED NULL,
    capacidad               INT NOT NULL,
    referencia_ubicacion    VARCHAR(255) NULL,
    estado                  ENUM('DISPONIBLE','BLOQUEADO','EN_MANTENIMIENTO')
                            NOT NULL DEFAULT 'DISPONIBLE',

    CONSTRAINT pk_espacios_academicos PRIMARY KEY (recurso_id),

    CONSTRAINT fk_espacio_recurso
        FOREIGN KEY (recurso_id) REFERENCES recursos_institucionales(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_espacio_piso
        FOREIGN KEY (piso_id) REFERENCES pisos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_espacio_tipo
        FOREIGN KEY (tipo_espacio_id) REFERENCES tipos_espacio(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_espacio_politica
        FOREIGN KEY (politica_liberacion_id) REFERENCES politicas_liberacion(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_espacio_capacidad CHECK (capacidad > 0),

    INDEX idx_espacio_piso (piso_id),
    INDEX idx_espacio_tipo (tipo_espacio_id),
    INDEX idx_espacio_estado (estado)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('espacios_academicos');
    }
};
