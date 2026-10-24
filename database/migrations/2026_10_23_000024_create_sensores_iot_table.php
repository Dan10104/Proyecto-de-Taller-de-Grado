<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE sensores_iot (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 espacio_actual_id BIGINT UNSIGNED NOT NULL,
 identificador VARCHAR(120) NOT NULL,
 tipo VARCHAR(100) NOT NULL,
 estado ENUM('HABILITADO','DESHABILITADO') NOT NULL DEFAULT 'HABILITADO',
 ultima_comunicacion DATETIME NULL,
 CONSTRAINT pk_sensores_iot PRIMARY KEY (id),
 CONSTRAINT uq_sensor_identificador UNIQUE (identificador),
 CONSTRAINT fk_sensor_espacio_actual FOREIGN KEY (espacio_actual_id)
 REFERENCES espacios_academicos(recurso_id) ON UPDATE CASCADE ON DELETE RESTRICT,
 INDEX idx_sensor_espacio (espacio_actual_id),
 INDEX idx_sensor_estado (estado)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sensores_iot');
    }
};
