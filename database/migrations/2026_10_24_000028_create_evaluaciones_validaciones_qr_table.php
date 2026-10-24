<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE evaluaciones_validaciones_qr (
 evaluacion_id BIGINT UNSIGNED NOT NULL,
 validacion_id BIGINT UNSIGNED NOT NULL,
 CONSTRAINT pk_eval_validaciones PRIMARY KEY (evaluacion_id,validacion_id),
 CONSTRAINT fk_eval_valid_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones_liberacion(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_eval_valid_validacion FOREIGN KEY (validacion_id) REFERENCES validaciones_qr(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_validaciones_qr');
    }
};
