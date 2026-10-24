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
CREATE TABLE validaciones_qr (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_qr_id        BIGINT UNSIGNED NULL,
    recurso_esperado_id BIGINT UNSIGNED NOT NULL,
    reserva_id          BIGINT UNSIGNED NULL,
    prestamo_id         BIGINT UNSIGNED NULL,
    entrega_id          BIGINT UNSIGNED NULL,
    devolucion_id       BIGINT UNSIGNED NULL,
    validado_por_id     BIGINT UNSIGNED NOT NULL,
    fecha_hora          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resultado           ENUM('VALIDA','INVALIDA') NOT NULL,
    tipo_operacion      ENUM('USO_ESPACIO','ENTREGA','DEVOLUCION') NOT NULL,
    motivo_rechazo      VARCHAR(500) NULL,
    hash_leido          VARCHAR(255) NOT NULL,

    CONSTRAINT pk_validaciones_qr PRIMARY KEY (id),

    CONSTRAINT fk_validacion_codigo
        FOREIGN KEY (codigo_qr_id) REFERENCES codigos_qr(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_validacion_recurso_esperado
        FOREIGN KEY (recurso_esperado_id) REFERENCES recursos_institucionales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_validacion_reserva
        FOREIGN KEY (reserva_id) REFERENCES reservas(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_validacion_prestamo
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_validacion_entrega
        FOREIGN KEY (entrega_id) REFERENCES entregas_prestamo(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_validacion_devolucion
        FOREIGN KEY (devolucion_id) REFERENCES devoluciones_prestamo(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_validacion_usuario
        FOREIGN KEY (validado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    -- Contexto mutuamente excluyente:
    -- uso de espacio -> reserva
    -- entrega/devolucion -> prestamo
    CONSTRAINT chk_validacion_contexto
        CHECK (
            (tipo_operacion = 'USO_ESPACIO'
                AND reserva_id IS NOT NULL
                AND prestamo_id IS NULL)
            OR
            (tipo_operacion IN ('ENTREGA','DEVOLUCION')
                AND reserva_id IS NULL
                AND prestamo_id IS NOT NULL)
        ),

    CONSTRAINT chk_validacion_entrega_devolucion
        CHECK (
            (tipo_operacion = 'USO_ESPACIO'
                AND entrega_id IS NULL
                AND devolucion_id IS NULL)
            OR
            (tipo_operacion = 'ENTREGA'
                AND devolucion_id IS NULL)
            OR
            (tipo_operacion = 'DEVOLUCION'
                AND entrega_id IS NULL)
        ),

    CONSTRAINT uq_validacion_entrega UNIQUE (entrega_id),
    CONSTRAINT uq_validacion_devolucion UNIQUE (devolucion_id),

    INDEX idx_validacion_codigo (codigo_qr_id),
    INDEX idx_validacion_reserva (reserva_id),
    INDEX idx_validacion_prestamo (prestamo_id),
    INDEX idx_validacion_usuario (validado_por_id),
    INDEX idx_validacion_fecha (fecha_hora)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('validaciones_qr');
    }
};
