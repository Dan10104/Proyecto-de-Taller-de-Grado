<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE prestamos (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(60) NOT NULL,
    equipamiento_id     BIGINT UNSIGNED NOT NULL,
    solicitante_id      BIGINT UNSIGNED NOT NULL,
    resuelto_por_id     BIGINT UNSIGNED NULL,
    inicio_previsto     DATETIME NOT NULL,
    fin_previsto        DATETIME NOT NULL,
    motivo              VARCHAR(500) NOT NULL,
    solicitado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado              ENUM('PENDIENTE','APROBADO','RECHAZADO',
                             'ENTREGADO','DEVUELTO')
                        NOT NULL DEFAULT 'PENDIENTE',
    resuelto_en         DATETIME NULL,
    motivo_rechazo      VARCHAR(500) NULL,

    CONSTRAINT pk_prestamos PRIMARY KEY (id),
    CONSTRAINT uq_prestamos_codigo UNIQUE (codigo),

    CONSTRAINT fk_prestamo_equipo
        FOREIGN KEY (equipamiento_id)
        REFERENCES equipamientos_tecnologicos(recurso_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_prestamo_solicitante
        FOREIGN KEY (solicitante_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_prestamo_resuelto_por
        FOREIGN KEY (resuelto_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_prestamo_fechas
        CHECK (fin_previsto > inicio_previsto),

    INDEX idx_prestamo_equipo_periodo
        (equipamiento_id, inicio_previsto, fin_previsto),

    INDEX idx_prestamo_solicitante (solicitante_id),
    INDEX idx_prestamo_estado (estado)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
