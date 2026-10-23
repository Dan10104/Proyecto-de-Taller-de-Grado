<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE recursos_institucionales (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(60) NOT NULL,
    nombre              VARCHAR(160) NOT NULL,

    CONSTRAINT pk_recursos_institucionales PRIMARY KEY (id),
    CONSTRAINT uq_recurso_codigo UNIQUE (codigo)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos_institucionales');
    }
};
