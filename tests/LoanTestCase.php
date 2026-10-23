<?php

namespace Tests;

use App\Exceptions\EquipmentConflictException;
use App\Models\EquipamientoTecnologico;
use App\Models\Prestamo;
use App\Models\TipoEquipamiento;
use App\Models\Usuario;
use App\Services\LoanService;

abstract class LoanTestCase extends SpaceTestCase
{
    protected function equipmentData(array $extra = []): array
    {
        return [...['codigo' => 'EQ-TEST-'.bin2hex(random_bytes(5)), 'nombre' => 'Equipo sintético', 'tipo_equipamiento_id' => TipoEquipamiento::factory()->create()->id, 'estado' => 'DISPONIBLE'], ...$extra];
    }

    protected function loanData(EquipamientoTecnologico $e, array $extra = []): array
    {
        return [...['equipamiento_id' => $e->getKey(), 'inicio_previsto' => '2090-01-10T08:00', 'fin_previsto' => '2090-01-10T09:00', 'motivo' => 'Prueba académica sintética'], ...$extra];
    }

    protected function loan(?Usuario $owner = null, ?EquipamientoTecnologico $equipment = null): Prestamo
    {
        return Prestamo::factory()->create(['solicitante_id' => ($owner ?? $this->user('Docente'))->id, 'equipamiento_id' => ($equipment ?? EquipamientoTecnologico::factory()->create())->getKey()]);
    }

    protected function loans(): LoanService
    {
        return app(LoanService::class);
    }

    protected function delivered(?Usuario $admin = null, ?EquipamientoTecnologico $equipment = null): Prestamo
    {
        $admin ??= $this->user('Administrador');
        $p = $this->loan(null, $equipment);
        $this->loans()->approve($admin, $p);

        return $this->loans()->deliver($admin, $p, $this->deliveryData());
    }

    protected function deliveryData(array $extra = []): array
    {
        return [...['condicion_entrega' => 'Equipo inspeccionado y completo', 'equipo_verificado' => true, 'receptor_verificado' => true], ...$extra];
    }

    protected function conflict(callable $operation): void
    {
        try {
            $operation();
            $this->fail('La operación incompatible debe rechazarse.');
        } catch (EquipmentConflictException $e) {
            $this->assertNotSame('', $e->getMessage());
        }
    }

    protected function incidentData(int $id, array $extra = []): array
    {
        return [...['recurso_id' => $id, 'tipo' => 'INCIDENCIA', 'descripcion' => 'Condición sintética que impide uso', 'inicio' => '2026-01-01T08:00'], ...$extra];
    }
}
