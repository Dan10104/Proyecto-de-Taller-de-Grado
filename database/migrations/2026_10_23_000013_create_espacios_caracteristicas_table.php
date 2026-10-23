<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE espacios_caracteristicas (
    espacio_id          BIGINT UNSIGNED NOT NULL,
    caracteristica_id   BIGINT UNSIGNED NOT NULL,

    CONSTRAINT pk_espacios_caracteristicas
        PRIMARY KEY (espacio_id, caracteristica_id),

    CONSTRAINT fk_esp_car_espacio
        FOREIGN KEY (espacio_id) REFERENCES espacios_academicos(recurso_id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_esp_car_caracteristica
        FOREIGN KEY (caracteristica_id) REFERENCES caracteristicas_espacio(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('espacios_caracteristicas');
    }
};
