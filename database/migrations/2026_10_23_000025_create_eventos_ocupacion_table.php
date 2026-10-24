<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE eventos_ocupacion (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 sensor_id BIGINT UNSIGNED NOT NULL,
 espacio_historico_id BIGINT UNSIGNED NOT NULL,
 tipo ENUM('PRESENCIA','OCUPACION') NOT NULL,
 valor DECIMAL(10,2) NULL,
 fecha_evento DATETIME NOT NULL,
 fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT pk_eventos_ocupacion PRIMARY KEY (id),
 CONSTRAINT fk_evento_sensor FOREIGN KEY (sensor_id) REFERENCES sensores_iot(id)
 ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_evento_espacio_historico FOREIGN KEY (espacio_historico_id) REFERENCES espacios_academicos(recurso_id)
 ON UPDATE CASCADE ON DELETE RESTRICT,
 INDEX idx_evento_sensor_fecha (sensor_id,fecha_evento),
 INDEX idx_evento_espacio_fecha (espacio_historico_id,fecha_evento)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_ocupacion');
    }
};
