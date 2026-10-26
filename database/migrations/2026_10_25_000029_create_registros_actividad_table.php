<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // D-12: only fk_registro_actor uses UPDATE RESTRICT (authorized CHECK compatibility).
        DB::unprepared(<<<'SQL'
CREATE TABLE registros_actividad (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 actor_id BIGINT UNSIGNED NULL,
 usuario_afectado_id BIGINT UNSIGNED NULL,
 recurso_id BIGINT UNSIGNED NULL,
 reserva_id BIGINT UNSIGNED NULL,
 prestamo_id BIGINT UNSIGNED NULL,
 incidencia_id BIGINT UNSIGNED NULL,
 accion VARCHAR(160) NOT NULL,
 fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 resultado ENUM('EXITO','RECHAZO','ERROR') NOT NULL,
 descripcion TEXT NULL,
 proceso_responsable VARCHAR(160) NULL,
 critica BOOLEAN NOT NULL DEFAULT FALSE,
 CONSTRAINT pk_registros_actividad PRIMARY KEY (id),
 CONSTRAINT fk_registro_actor FOREIGN KEY (actor_id) REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
 CONSTRAINT fk_registro_usuario_afectado FOREIGN KEY (usuario_afectado_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_registro_recurso FOREIGN KEY (recurso_id) REFERENCES recursos_institucionales(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_registro_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_registro_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_registro_incidencia FOREIGN KEY (incidencia_id) REFERENCES incidencias_mantenimiento(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_registro_responsable CHECK (actor_id IS NOT NULL OR proceso_responsable IS NOT NULL),
 INDEX idx_registro_fecha (fecha_hora),
 INDEX idx_registro_actor (actor_id),
 INDEX idx_registro_recurso (recurso_id),
 INDEX idx_registro_reserva (reserva_id),
 INDEX idx_registro_prestamo (prestamo_id),
 INDEX idx_registro_critica (critica)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_actividad');
    }
};
