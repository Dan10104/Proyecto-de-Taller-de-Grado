<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE incidencias_mantenimiento (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    recurso_id          BIGINT UNSIGNED NOT NULL,
    reportado_por_id    BIGINT UNSIGNED NOT NULL,
    cerrado_por_id      BIGINT UNSIGNED NULL,
    devolucion_id       BIGINT UNSIGNED NULL,
    tipo                ENUM('INCIDENCIA','MANTENIMIENTO') NOT NULL,
    descripcion         TEXT NOT NULL,
    inicio              DATETIME NOT NULL,
    fin_previsto        DATETIME NULL,
    cerrada_en          DATETIME NULL,
    solucion            TEXT NULL,
    estado              ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',

    CONSTRAINT pk_incidencias_mantenimiento PRIMARY KEY (id),

    CONSTRAINT fk_incidencia_recurso
        FOREIGN KEY (recurso_id) REFERENCES recursos_institucionales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_incidencia_reportado_por
        FOREIGN KEY (reportado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_incidencia_cerrado_por
        FOREIGN KEY (cerrado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_incidencia_devolucion
        FOREIGN KEY (devolucion_id) REFERENCES devoluciones_prestamo(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_incidencia_fin_previsto
        CHECK (fin_previsto IS NULL OR fin_previsto >= inicio),

    CONSTRAINT chk_incidencia_cierre
        CHECK (cerrada_en IS NULL OR cerrada_en >= inicio),

    INDEX idx_incidencia_recurso (recurso_id),
    INDEX idx_incidencia_estado (estado)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias_mantenimiento');
    }
};
