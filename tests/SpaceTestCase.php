<?php

namespace Tests;

use App\Models\BloqueoEspacio;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\TipoEspacio;
use App\Models\Usuario;

abstract class SpaceTestCase extends SecurityTestCase
{
    protected function spaceData(array $extra = []): array
    {
        return [...['codigo' => 'ESP-TEST-'.bin2hex(random_bytes(5)), 'nombre' => 'Espacio sintético', 'piso_id' => Piso::factory()->create()->id, 'tipo_espacio_id' => TipoEspacio::factory()->create()->id, 'capacidad' => 30, 'referencia_ubicacion' => 'Ubicación de prueba', 'estado' => 'DISPONIBLE', 'caracteristicas' => []], ...$extra];
    }

    protected function blockFor(EspacioAcademico $space, string $start, string $end, bool $active = true, ?Usuario $actor = null): BloqueoEspacio
    {
        $block = new BloqueoEspacio;
        $block->forceFill(['espacio_id' => $space->getKey(), 'responsable_id' => ($actor ?? $this->user())->id, 'inicio' => $start, 'fin' => $end, 'motivo' => 'Bloqueo sintético', 'activo' => $active])->save();

        return $block;
    }

    protected function availabilityUrl(array $extra = []): string
    {
        return '/disponibilidad?'.http_build_query([...['fecha' => '2027-01-10', 'hora_inicio' => '08:00', 'hora_fin' => '09:00'], ...$extra]);
    }
}
