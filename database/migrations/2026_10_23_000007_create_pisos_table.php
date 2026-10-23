<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE pisos (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero              INT NOT NULL,
    nombre              VARCHAR(100) NOT NULL,

    CONSTRAINT pk_pisos PRIMARY KEY (id),
    CONSTRAINT uq_pisos_numero UNIQUE (numero)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pisos');
    }
};
