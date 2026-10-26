<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE evidencias_trazabilidad (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 registro_actividad_id BIGINT UNSIGNED NOT NULL,
 algoritmo_hash VARCHAR(50) NOT NULL,
 hash VARCHAR(255) NOT NULL,
 version_canonica VARCHAR(60) NOT NULL,
 referencia_externa VARCHAR(255) NULL,
 registrada_localmente_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 registrada_externamente_en DATETIME NULL,
 estado ENUM('PENDIENTE','REGISTRADA') NOT NULL DEFAULT 'PENDIENTE',
 ultima_verificacion DATETIME NULL,
 resultado_verificacion ENUM('VALIDO','INCONSISTENTE','PENDIENTE','NO_VERIFICABLE') NOT NULL DEFAULT 'PENDIENTE',
 CONSTRAINT pk_evidencias_trazabilidad PRIMARY KEY (id),
 CONSTRAINT uq_evidencia_registro UNIQUE (registro_actividad_id),
 CONSTRAINT uq_evidencia_hash UNIQUE (hash),
 CONSTRAINT fk_evidencia_registro FOREIGN KEY (registro_actividad_id) REFERENCES registros_actividad(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 INDEX idx_evidencia_estado (estado),
 INDEX idx_evidencia_referencia (referencia_externa)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_trazabilidad');
    }
};
