<?php

namespace App\Services\Reporting;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class ReportRegistry
{
    public function definitions(): array
    {
        $statesR = ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA', 'LIBERADA', 'FINALIZADA'];
        $statesP = ['PENDIENTE', 'APROBADO', 'RECHAZADO', 'ENTREGADO', 'DEVUELTO'];

        return [
            'reservas' => new ReportDefinition('reservas', 'Reservas', ['reservas.ver_administrativas'], true,
                ['estado' => $statesR, 'espacio_id' => 'reference', 'piso_id' => 'reference', 'solicitante_id' => 'reference'],
                [['codigo', 'Código', 'string'], ['espacio', 'Espacio', 'string'], ['solicitante', 'Solicitante', 'string'], ['inicio', 'Inicio', 'datetime'], ['fin', 'Fin', 'datetime'], ['estado', 'Estado', 'string'], ['solicitada_en', 'Solicitada en', 'datetime'], ['resuelta_en', 'Resuelta en', 'datetime'], ['cancelada_en', 'Cancelada en', 'datetime']]),
            'espacios' => new ReportDefinition('espacios', 'Disponibilidad y espacios', ['espacios.ver'], false,
                ['vista' => ['inventario', 'disponibilidad'], 'piso_id' => 'reference', 'tipo_espacio_id' => 'reference', 'estado' => ['DISPONIBLE', 'BLOQUEADO', 'EN_MANTENIMIENTO'], 'disponible' => ['si', 'no'], 'inicio' => 'instant', 'fin' => 'instant'],
                [['codigo', 'Código', 'string'], ['nombre', 'Nombre', 'string'], ['piso', 'Piso', 'string'], ['tipo', 'Tipo', 'string'], ['capacidad', 'Capacidad', 'integer'], ['estado', 'Estado operativo', 'string']]),
            'equipamiento' => new ReportDefinition('equipamiento', 'Equipamiento tecnológico', ['equipamientos.ver'], false,
                ['estado' => ['DISPONIBLE', 'PRESTADO', 'EN_MANTENIMIENTO', 'BAJA'], 'tipo_equipamiento_id' => 'reference', 'espacio_base_id' => 'reference', 'sin_ubicacion' => ['si']],
                [['codigo', 'Código', 'string'], ['nombre', 'Nombre', 'string'], ['tipo', 'Tipo', 'string'], ['marca', 'Marca', 'string'], ['modelo', 'Modelo', 'string'], ['numero_serie', 'Número de serie', 'string'], ['ubicacion_base', 'Ubicación base', 'string'], ['estado', 'Estado', 'string']]),
            'prestamos' => new ReportDefinition('prestamos', 'Préstamos y vencimientos', ['prestamos.ver_administrativos'], true,
                ['estado' => $statesP, 'equipamiento_id' => 'reference', 'solicitante_id' => 'reference', 'vencido' => ['si', 'no']],
                [['codigo', 'Código', 'string'], ['equipamiento', 'Equipamiento', 'string'], ['solicitante', 'Solicitante', 'string'], ['inicio_previsto', 'Inicio previsto', 'datetime'], ['fin_previsto', 'Fin previsto', 'datetime'], ['estado', 'Estado', 'string'], ['vencido', 'Vencido', 'boolean'], ['solicitado_en', 'Solicitado en', 'datetime'], ['resuelto_en', 'Resuelto en', 'datetime']]),
            'incidencias' => new ReportDefinition('incidencias', 'Incidencias y mantenimiento', ['incidencias.ver'], true,
                ['tipo' => ['INCIDENCIA', 'MANTENIMIENTO'], 'estado' => ['ABIERTA', 'CERRADA'], 'recurso_id' => 'reference'],
                [['recurso', 'Recurso', 'string'], ['tipo', 'Tipo', 'string'], ['inicio', 'Inicio', 'datetime'], ['fin_previsto', 'Fin previsto', 'datetime'], ['estado', 'Estado', 'string'], ['reportante', 'Reportante', 'string'], ['cerrada_en', 'Cerrada en', 'datetime']]),
            'iot' => new ReportDefinition('iot', 'Ocupación IoT', ['iot.monitoreo.ver'], true,
                ['espacio_historico_id' => 'reference', 'sensor_id' => 'reference', 'tipo' => ['PRESENCIA', 'OCUPACION']],
                [['espacio_historico', 'Espacio histórico', 'string'], ['sensor', 'Sensor', 'string'], ['tipo', 'Tipo', 'string'], ['valor', 'Valor', 'decimal'], ['fecha_evento', 'Fecha evento', 'datetime'], ['fecha_recepcion', 'Fecha recepción', 'datetime']]),
            'actividad-operacional' => new ReportDefinition('actividad-operacional', 'Actividad operacional', [], true,
                ['categoria' => array_keys(ActivityReportQuery::FAMILIES), 'accion' => array_merge(...array_column(ActivityReportQuery::FAMILIES, 'actions')), 'resultado' => ['EXITO', 'RECHAZO', 'ERROR']],
                [['fecha', 'Fecha local', 'date'], ['accion', 'Acción', 'string'], ['resultado', 'Resultado', 'string'], ['responsable', 'Actor / proceso', 'string'], ['categoria', 'Categoría', 'string'], ['referencia', 'Referencia técnica', 'string'], ['cantidad', 'Cantidad', 'integer']]),
        ];
    }

    public function get(string $id): ReportDefinition
    {
        return $this->definitions()[$id] ?? throw ValidationException::withMessages(['reporte' => 'Seleccione un reporte del catálogo autorizado.']);
    }

    public function authorize(ReportDefinition $definition, ReportingAccess $access, bool $export = false): void
    {
        $access->require('reportes.ver', ...$definition->permissions);
        if ($export) {
            $access->require('reportes.exportar');
        }
        if (! $access->administrative()) {
            throw new AuthorizationException;
        }
    }

    public function available(ReportingAccess $access): array
    {
        if (! $access->has('reportes.ver') || ! $access->administrative()) {
            return [];
        }
        $items = [];
        foreach ($this->definitions() as $d) {
            if ($access->has(...$d->permissions)) {
                $filters = $d->filters;
                if ($d->id === 'actividad-operacional') {
                    $families = ActivityReportQuery::allowed($access);
                    $filters['categoria'] = array_keys($families);
                    $filters['accion'] = array_merge(...array_column($families, 'actions'));
                }
                $items[] = ['id' => $d->id, 'titulo' => $d->title, 'rango' => $d->dated, 'filtros' => $filters];
            }
        }

        return $items;
    }
}
