<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE sesiones_acceso (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id          BIGINT UNSIGNED NOT NULL,
    token_hash          VARCHAR(255) NOT NULL,
    creada_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en           DATETIME NOT NULL,
    revocada_en         DATETIME NULL,

    CONSTRAINT pk_sesiones_acceso PRIMARY KEY (id),
    CONSTRAINT uq_sesiones_token_hash UNIQUE (token_hash),

    CONSTRAINT fk_sesion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_sesion_expiracion
        CHECK (expira_en > creada_en),

    INDEX idx_sesion_usuario (usuario_id)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_acceso');
    }
};
