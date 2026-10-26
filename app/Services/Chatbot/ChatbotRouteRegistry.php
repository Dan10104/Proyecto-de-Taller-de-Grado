<?php

namespace App\Services\Chatbot;

class ChatbotRouteRegistry
{
    private const ROUTES = [
        'inicio' => ['inicio', [], [], 'Ir a Inicio'],
        'espacios' => ['espacios.index', [], ['espacios.ver'], 'Consultar espacios'],
        'reservas.disponibilidad' => ['disponibilidad.index', [], ['espacios.ver'], 'Ir a Disponibilidad'],
        'reservas.propias' => ['reservas.index', ['alcance' => 'propias'], ['reservas.ver_propias'], 'Ver mis reservas'],
        'reservas.administrativas' => ['reservas.index', ['alcance' => 'administrativas'], ['reservas.ver_administrativas'], 'Consultar reservas'],
        'equipamientos' => ['equipamientos.index', [], ['equipamientos.ver'], 'Consultar equipamiento'],
        'equipamientos.disponibilidad' => ['equipamientos.availability', [], ['equipamientos.ver'], 'Ver disponibilidad de equipamiento'],
        'prestamos.propios' => ['prestamos.index', ['alcance' => 'propios'], ['prestamos.ver_propios'], 'Ver mis préstamos'],
        'prestamos.administrativos' => ['prestamos.index', ['alcance' => 'administrativos'], ['prestamos.ver_administrativos'], 'Consultar préstamos'],
        'incidencias' => ['incidencias.index', [], ['incidencias.ver'], 'Consultar incidencias'],
        'qr.gestion' => ['qr.index', [], ['qr.gestionar'], 'Gestionar QR'],
        'iot.monitoreo' => ['iot.monitoring', [], ['iot.monitoreo.ver'], 'Abrir monitoreo'],
        'iot.sensores' => ['iot.sensors.index', [], ['iot.sensores.ver'], 'Consultar sensores'],
        'liberacion.propias' => ['release.evaluations.index', ['alcance' => 'propias'], ['liberacion.evaluaciones.ver_propias'], 'Ver mis evaluaciones'],
        'liberacion.administrativas' => ['release.evaluations.index', ['alcance' => 'administrativas'], ['liberacion.evaluaciones.ver_administrativas'], 'Consultar evaluaciones'],
        'liberacion.politicas' => ['release.policies.index', [], ['liberacion.politicas.ver'], 'Consultar políticas'],
        'trazabilidad' => ['trazabilidad.index', [], ['trazabilidad.evidencias.ver'], 'Consultar trazabilidad'],
        'notificaciones' => ['notifications.index', [], ['notificaciones.ver_propias'], 'Ver mis notificaciones'],
        'dashboard' => ['dashboard', [], ['dashboard.ver'], 'Abrir Dashboard'],
        'reportes' => ['reports.index', [], ['reportes.ver'], 'Abrir Reportes'],
    ];

    public function keys(): array
    {
        return array_keys(self::ROUTES);
    }

    public function resolve(?string $key, array $permissions): ?array
    {
        if (! $key || ! isset(self::ROUTES[$key]) || ! in_array('chatbot.consultar', $permissions, true)) {
            return null;
        }
        [$name, $params, $required, $label] = self::ROUTES[$key];
        if (array_diff($required, $permissions)) {
            return null;
        }
        if ($key === 'qr.gestion' && ! array_intersect(['espacios.ver', 'equipamientos.ver'], $permissions)) {
            return null;
        }

        return ['label' => $label, 'href' => route($name, $params, false)];
    }
}
