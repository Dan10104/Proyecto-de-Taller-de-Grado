<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE politicas_liberacion (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre                      VARCHAR(120) NOT NULL,
    version                     INT NOT NULL,
    tolerancia_min              INT NOT NULL DEFAULT 0,
    requiere_qr                 BOOLEAN NOT NULL DEFAULT FALSE,
    usa_iot                     BOOLEAN NOT NULL DEFAULT FALSE,
    max_antiguedad_lectura_min  INT NULL,
    modo                        ENUM('ALERTA','AUTOMATICA') NOT NULL,
    activa                      BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_politicas_liberacion PRIMARY KEY (id),
    CONSTRAINT uq_politica_nombre_version UNIQUE (nombre, version),

    CONSTRAINT chk_politica_tolerancia
        CHECK (tolerancia_min >= 0),

    CONSTRAINT chk_politica_antiguedad
        CHECK (max_antiguedad_lectura_min IS NULL OR max_antiguedad_lectura_min >= 0)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_liberacion');
    }
};
