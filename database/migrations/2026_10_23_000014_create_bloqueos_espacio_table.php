<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE bloqueos_espacio (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    espacio_id          BIGINT UNSIGNED NOT NULL,
    responsable_id      BIGINT UNSIGNED NOT NULL,
    inicio              DATETIME NOT NULL,
    fin                 DATETIME NOT NULL,
    motivo              VARCHAR(500) NOT NULL,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_bloqueos_espacio PRIMARY KEY (id),

    CONSTRAINT fk_bloqueo_espacio
        FOREIGN KEY (espacio_id) REFERENCES espacios_academicos(recurso_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_bloqueo_responsable
        FOREIGN KEY (responsable_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_bloqueo_fechas CHECK (fin > inicio),

    INDEX idx_bloqueo_espacio_periodo (espacio_id, inicio, fin),
    INDEX idx_bloqueo_activo (activo)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_espacio');
    }
};
