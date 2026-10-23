<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE asignaciones_roles (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id          BIGINT UNSIGNED NOT NULL,
    rol_id              BIGINT UNSIGNED NOT NULL,
    asignado_por_id     BIGINT UNSIGNED NOT NULL,
    retirado_por_id     BIGINT UNSIGNED NULL,
    fecha_asignacion    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_retiro        DATETIME NULL,

    CONSTRAINT pk_asignaciones_roles PRIMARY KEY (id),

    CONSTRAINT fk_asigrol_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_asigrol_rol
        FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_asigrol_asignado_por
        FOREIGN KEY (asignado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_asigrol_retirado_por
        FOREIGN KEY (retirado_por_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_asigrol_fechas
        CHECK (fecha_retiro IS NULL OR fecha_retiro >= fecha_asignacion),

    INDEX idx_asigrol_usuario (usuario_id),
    INDEX idx_asigrol_rol (rol_id)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_roles');
    }
};
