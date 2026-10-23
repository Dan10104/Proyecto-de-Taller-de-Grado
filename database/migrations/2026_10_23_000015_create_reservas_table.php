<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE reservas (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(60) NOT NULL,
    espacio_id          BIGINT UNSIGNED NOT NULL,
    solicitante_id      BIGINT UNSIGNED NOT NULL,
    resuelta_por_id     BIGINT UNSIGNED NULL,
    cancelada_por_id    BIGINT UNSIGNED NULL,
    inicio              DATETIME NOT NULL,
    fin                 DATETIME NOT NULL,
    proposito           VARCHAR(500) NOT NULL,
    solicitada_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado              ENUM('PENDIENTE','APROBADA','RECHAZADA',
                             'CANCELADA','LIBERADA','FINALIZADA')
                        NOT NULL DEFAULT 'PENDIENTE',
    resuelta_en         DATETIME NULL,
    motivo_rechazo      VARCHAR(500) NULL,
    cancelada_en        DATETIME NULL,
    motivo_cancelacion  VARCHAR(500) NULL,

    CONSTRAINT pk_reservas PRIMARY KEY (id),
    CONSTRAINT uq_reservas_codigo UNIQUE (codigo),

    CONSTRAINT fk_reserva_espacio
        FOREIGN KEY (espacio_id) REFERENCES espacios_academicos(recurso_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_reserva_solicitante
        FOREIGN KEY (solicitante_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_reserva_resuelta_por
        FOREIGN KEY (resuelta_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_reserva_cancelada_por
        FOREIGN KEY (cancelada_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_reserva_fechas CHECK (fin > inicio),

    INDEX idx_reserva_espacio_periodo (espacio_id, inicio, fin),
    INDEX idx_reserva_solicitante (solicitante_id),
    INDEX idx_reserva_estado (estado)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
