<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Support\Facades\DB;

class ReportAuditService
{
    public function record(Usuario $actor, array $metadata, string $format, int $count): void
    {
        // Filter keys/values came from the definition, never from display labels or raw request data.
        $safe = json_encode($metadata['filtros'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $description = 'Reporte '.$metadata['reporte'].'; formato '.$format.'; filas '.$count.'; corte '.$metadata['generado_en'].'; filtros '.$safe;
        DB::transaction(function () use ($actor, $description) {
            app(ActivityRecorder::class)->record(['actor_id' => $actor->id, 'usuario_afectado_id' => null, 'recurso_id' => null, 'reserva_id' => null, 'prestamo_id' => null, 'incidencia_id' => null,
                'accion' => 'REPORTE_EXPORTADO', 'fecha_hora' => now(), 'resultado' => 'EXITO', 'descripcion' => mb_substr($description, 0, 500), 'proceso_responsable' => null, 'critica' => false]);
        });
    }
}
