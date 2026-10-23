<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE entregas_prestamo (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    prestamo_id             BIGINT UNSIGNED NOT NULL,
    entregado_por_id        BIGINT UNSIGNED NOT NULL,
    receptor_id             BIGINT UNSIGNED NOT NULL,
    fecha_hora              DATETIME NOT NULL,
    condicion_entrega       VARCHAR(255) NOT NULL,
    observacion             VARCHAR(500) NULL,
    recepcion_confirmada_en DATETIME NULL,

    CONSTRAINT pk_entregas_prestamo PRIMARY KEY (id),
    CONSTRAINT uq_entrega_prestamo UNIQUE (prestamo_id),

    CONSTRAINT fk_entrega_prestamo
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_entrega_entregado_por
        FOREIGN KEY (entregado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_entrega_receptor
        FOREIGN KEY (receptor_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas_prestamo');
    }
};
