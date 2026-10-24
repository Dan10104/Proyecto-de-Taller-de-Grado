<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE evaluaciones_liberacion (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 reserva_id BIGINT UNSIGNED NOT NULL,
 politica_id BIGINT UNSIGNED NOT NULL,
 fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 resultado ENUM('MANTENER_POR_USO','NO_DETERMINABLE','ALERTAR','LIBERAR') NOT NULL,
 motivo VARCHAR(500) NULL,
 CONSTRAINT pk_evaluaciones_liberacion PRIMARY KEY (id),
 CONSTRAINT fk_eval_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_eval_politica FOREIGN KEY (politica_id) REFERENCES politicas_liberacion(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 INDEX idx_eval_reserva_fecha (reserva_id,fecha_hora),
 INDEX idx_eval_politica (politica_id)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_liberacion');
    }
};
