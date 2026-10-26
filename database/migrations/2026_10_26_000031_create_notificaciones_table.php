<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE notificaciones (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 destinatario_id BIGINT UNSIGNED NOT NULL,
 registro_actividad_id BIGINT UNSIGNED NOT NULL,
 titulo VARCHAR(160) NOT NULL,
 mensaje TEXT NOT NULL,
 tipo ENUM('SOLICITUD','DECISION','CANCELACION','ENTREGA','DEVOLUCION','VENCIMIENTO','INCIDENCIA','LIBERACION') NOT NULL,
 creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 leida_en DATETIME NULL,
 CONSTRAINT pk_notificaciones PRIMARY KEY (id),
 CONSTRAINT fk_notificacion_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_notificacion_evento_origen FOREIGN KEY (registro_actividad_id) REFERENCES registros_actividad(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 INDEX idx_notificacion_destinatario (destinatario_id, leida_en),
 INDEX idx_notificacion_fecha (creada_en)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
