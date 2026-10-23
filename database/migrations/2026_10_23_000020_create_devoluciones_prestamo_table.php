<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE devoluciones_prestamo (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    prestamo_id             BIGINT UNSIGNED NOT NULL,
    recibido_por_id         BIGINT UNSIGNED NOT NULL,
    confirmado_por_id       BIGINT UNSIGNED NULL,
    registrada_en           DATETIME NOT NULL,
    confirmada_en           DATETIME NULL,
    condicion               ENUM('ADECUADA','CON_INCIDENCIA','NO_DETERMINABLE')
                            NOT NULL,
    descripcion_condicion   VARCHAR(500) NULL,
    observacion             VARCHAR(500) NULL,
    estado                  ENUM('PENDIENTE_REVISION','CONFIRMADA')
                            NOT NULL DEFAULT 'PENDIENTE_REVISION',

    CONSTRAINT pk_devoluciones_prestamo PRIMARY KEY (id),
    CONSTRAINT uq_devolucion_prestamo UNIQUE (prestamo_id),

    CONSTRAINT fk_devolucion_prestamo
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_devolucion_recibido_por
        FOREIGN KEY (recibido_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_devolucion_confirmado_por
        FOREIGN KEY (confirmado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('devoluciones_prestamo');
    }
};
