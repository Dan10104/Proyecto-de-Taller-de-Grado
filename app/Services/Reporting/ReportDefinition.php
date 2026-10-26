<?php

namespace App\Services\Reporting;

final readonly class ReportDefinition
{
    public function __construct(public string $id, public string $title, public array $permissions, public bool $dated, public array $filters, private array $fields) {}

    public function columns(ReportingAccess $access, array $filters): array
    {
        $columns = [];
        foreach ($this->fields as [$key, $label, $type]) {
            if ($key === 'numero_serie' && ! $access->has('equipamientos.gestionar')) {
                continue;
            }
            $columns[] = compact('key', 'label', 'type');
        }
        if ($this->id === 'espacios' && ($filters['vista'] ?? 'inventario') === 'disponibilidad') {
            $columns[] = ['key' => 'disponible', 'label' => 'Disponible para el periodo', 'type' => 'boolean'];
        }

        return $columns;
    }
}
