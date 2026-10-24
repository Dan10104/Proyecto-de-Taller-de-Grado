<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seven CHECK-related FK updates use RESTRICT: user-authorized MySQL 3823 compatibility exception.
        // See docs/QR_IMPLEMENTATION.md; original source SQL remains unchanged.
        DB::unprepared(<<<'SQL'
CREATE TABLE codigos_qr (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    recurso_id          BIGINT UNSIGNED NULL,
    reserva_id          BIGINT UNSIGNED NULL,
    prestamo_id         BIGINT UNSIGNED NULL,
    token_hash          VARCHAR(255) NOT NULL,
    generada_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vence_en            DATETIME NULL,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_codigos_qr PRIMARY KEY (id),
    CONSTRAINT uq_codigo_qr_token_hash UNIQUE (token_hash),

    CONSTRAINT fk_qr_recurso
        FOREIGN KEY (recurso_id) REFERENCES recursos_institucionales(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_qr_reserva
        FOREIGN KEY (reserva_id) REFERENCES reservas(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_qr_prestamo
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    -- Un QR identifica exactamente un objetivo:
    -- recurso institucional, reserva o prestamo.
    CONSTRAINT chk_qr_objetivo
        CHECK (
            (recurso_id IS NOT NULL) +
            (reserva_id IS NOT NULL) +
            (prestamo_id IS NOT NULL) = 1
        ),

    CONSTRAINT chk_qr_vigencia
        CHECK (vence_en IS NULL OR vence_en >= generada_en),

    INDEX idx_qr_recurso (recurso_id),
    INDEX idx_qr_reserva (reserva_id),
    INDEX idx_qr_prestamo (prestamo_id),
    INDEX idx_qr_activo (activo)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_qr');
    }
};
