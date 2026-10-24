<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE evaluaciones_eventos_ocupacion (
 evaluacion_id BIGINT UNSIGNED NOT NULL,
 evento_id BIGINT UNSIGNED NOT NULL,
 CONSTRAINT pk_eval_eventos PRIMARY KEY (evaluacion_id,evento_id),
 CONSTRAINT fk_eval_evento_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones_liberacion(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_eval_evento_evento FOREIGN KEY (evento_id) REFERENCES eventos_ocupacion(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_eventos_ocupacion');
    }
};
