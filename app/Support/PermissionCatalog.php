<?php

namespace App\Support;

use App\Enums\RolInstitucional;

class PermissionCatalog
{
    public const DEFINITIONS = [
        'dashboard.ver' => ['nombre' => 'Visualizar dashboard autorizado', 'fuente' => 'RF22 / CU27'],
        'reportes.ver' => ['nombre' => 'Consultar reportes administrativos', 'fuente' => 'RF23 / CU28'],
        'reportes.exportar' => ['nombre' => 'Exportar reportes autorizados', 'fuente' => 'RF23 / CU28'],
        'notificaciones.ver_propias' => ['nombre' => 'Consultar y leer notificaciones propias', 'fuente' => 'RF21 / CU26'],
        'trazabilidad.evidencias.ver' => ['nombre' => 'Consultar evidencias de trazabilidad', 'fuente' => 'RF19–20 / CU24–25 / D-12'],
        'trazabilidad.evidencias.verificar' => ['nombre' => 'Verificar evidencias de trazabilidad', 'fuente' => 'RF20 / CU25 / D-12'],
        'trazabilidad.reintentar' => ['nombre' => 'Reintentar registro de evidencias pendientes', 'fuente' => 'RF19 / CU24 / D-12'],
        'liberacion.politicas.ver' => ['nombre' => 'Consultar políticas de liberación', 'fuente' => 'RF18 / CU23 / D-08'],
        'liberacion.politicas.gestionar' => ['nombre' => 'Gestionar versiones y asignación de políticas', 'fuente' => 'RF18 / CU23 / D-08'],
        'liberacion.evaluaciones.ver_administrativas' => ['nombre' => 'Consultar evaluaciones administrativas', 'fuente' => 'RF18 / CU23 / D-08'],
        'liberacion.evaluaciones.ver_propias' => ['nombre' => 'Consultar evaluaciones de reservas propias', 'fuente' => 'RF18 / CU23 / D-08'],
        'iot.sensores.ver' => ['nombre' => 'Consultar sensores IoT', 'fuente' => 'RF16 / CU21 / D-10'],
        'iot.sensores.gestionar' => ['nombre' => 'Gestionar y provisionar sensores IoT', 'fuente' => 'RF16 / CU21 / D-10'],
        'iot.monitoreo.ver' => ['nombre' => 'Consultar monitoreo e historial IoT', 'fuente' => 'RF17 / CU22 / D-10'],
        'qr.gestionar' => ['nombre' => 'Gestionar y rotar códigos QR', 'fuente' => 'D-09 / RF8 / CU11–20'],
        'qr.validar_uso_espacio' => ['nombre' => 'Validar uso de espacio mediante QR', 'fuente' => 'RF8 / CU11'],
        'qr.validar_operacion' => ['nombre' => 'Validar entrega o devolución mediante QR', 'fuente' => 'RF13–14 / CU17–18 / CU20'],
        'equipamientos.ver' => ['nombre' => 'Consultar equipamiento y disponibilidad', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'equipamientos.gestionar' => ['nombre' => 'Gestionar equipamiento y tipos', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'equipamientos.cambiar_estado' => ['nombre' => 'Cambiar estado operativo de equipamiento', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'prestamos.ver_propios' => ['nombre' => 'Consultar préstamos propios', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'prestamos.ver_administrativos' => ['nombre' => 'Consultar préstamos administrativos', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'prestamos.solicitar' => ['nombre' => 'Solicitar préstamo', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'prestamos.resolver' => ['nombre' => 'Aprobar o rechazar préstamo', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'prestamos.registrar_entrega' => ['nombre' => 'Registrar entrega de equipamiento', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'prestamos.registrar_devolucion' => ['nombre' => 'Registrar y confirmar devolución', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'incidencias.ver' => ['nombre' => 'Consultar incidencias y mantenimiento', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'incidencias.gestionar' => ['nombre' => 'Registrar incidencias y mantenimiento', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'incidencias.cerrar' => ['nombre' => 'Cerrar incidencias y mantenimiento', 'fuente' => 'RF9–15 / CU12–19: contrato Fase 4.5'],
        'reservas.ver_propias' => ['nombre' => 'Consultar reservas propias', 'fuente' => 'RF6–7 / CU08: seguimiento del titular'],
        'reservas.ver_administrativas' => ['nombre' => 'Consultar reservas administrativas', 'fuente' => 'RF6–7 / CU08: consulta de gestión autorizada'],
        'reservas.solicitar' => ['nombre' => 'Solicitar reserva', 'fuente' => 'RF6 / CU07: Docente o Estudiante autorizado'],
        'reservas.resolver' => ['nombre' => 'Aprobar o rechazar solicitudes de reserva', 'fuente' => 'RF7 / CU09: resolver pendientes'],
        'reservas.cancelar_propias' => ['nombre' => 'Cancelar reservas propias', 'fuente' => 'RF7 / CU10: titular antes del inicio'],
        'reservas.cancelar_administrativas' => ['nombre' => 'Cancelar reservas administrativamente', 'fuente' => 'RF7 / CU10: responsable autorizado antes del inicio'],
        'espacios.ver' => ['nombre' => 'Consultar espacios y disponibilidad', 'fuente' => 'RF4–5 / CU05–06: catálogo, detalle y disponibilidad base'],
        'espacios.gestionar' => ['nombre' => 'Registrar y editar espacios', 'fuente' => 'RF4 / CU05: datos, características y catálogos auxiliares'],
        'espacios.cambiar_estado' => ['nombre' => 'Cambiar estado de espacios', 'fuente' => 'RF4 / CU05: estado operativo'],
        'espacios.gestionar_bloqueos' => ['nombre' => 'Gestionar bloqueos de espacios', 'fuente' => 'RF4–5 / CU05–06: registrar y desactivar restricciones temporales'],
        'usuarios.ver' => ['nombre' => 'Consultar usuarios', 'fuente' => 'RF2 / CU03: listar, buscar, filtrar y consultar'],
        'usuarios.crear' => ['nombre' => 'Registrar usuarios', 'fuente' => 'RF2 / CU03: registrar cuenta'],
        'usuarios.editar' => ['nombre' => 'Editar usuarios', 'fuente' => 'RF2 / CU03: actualizar datos y contraseña administrativa'],
        'usuarios.deshabilitar' => ['nombre' => 'Deshabilitar usuarios', 'fuente' => 'RF2 / CU03: baja lógica'],
        'roles.ver' => ['nombre' => 'Consultar roles', 'fuente' => 'RF3 / CU04: consultar roles y asignaciones'],
        'roles.crear' => ['nombre' => 'Registrar rol institucional', 'fuente' => 'RF3 / CU04: alta dentro del catálogo institucional'],
        'roles.editar' => ['nombre' => 'Editar roles', 'fuente' => 'RF3 / CU04: actualizar datos compatibles'],
        'permisos.ver' => ['nombre' => 'Consultar permisos', 'fuente' => 'RF3 / CU04: consultar catálogo de permisos'],
        'roles.gestionar_permisos' => ['nombre' => 'Configurar permisos de rol', 'fuente' => 'RF3 / CU04: asociar y retirar permisos'],
        'roles.asignar' => ['nombre' => 'Asignar roles', 'fuente' => 'RF2–3 / CU03–04: asignar rol a usuario'],
        'roles.retirar' => ['nombre' => 'Retirar roles', 'fuente' => 'RF3 / CU04: retirar conservando historia'],
    ];

    public static function eligibleRoles(string $code): array
    {
        if (! array_key_exists($code, self::DEFINITIONS)) {
            return [];
        }
        if (in_array($code, ['dashboard.ver', 'notificaciones.ver_propias', 'qr.validar_uso_espacio', 'reservas.ver_propias', 'reservas.cancelar_propias', 'espacios.ver', 'equipamientos.ver'], true)) {
            return array_column(RolInstitucional::cases(), 'value');
        }
        if (in_array($code, ['liberacion.evaluaciones.ver_propias', 'reservas.solicitar', 'prestamos.solicitar', 'prestamos.ver_propios'], true)) {
            return ['Docente', 'Estudiante'];
        }
        if (str_starts_with($code, 'reportes.') || str_starts_with($code, 'liberacion.') || str_starts_with($code, 'iot.') || str_starts_with($code, 'qr.') || str_starts_with($code, 'espacios.') || str_starts_with($code, 'reservas.') || str_starts_with($code, 'equipamientos.') || str_starts_with($code, 'prestamos.') || str_starts_with($code, 'incidencias.')) {
            return ['Administrador', 'Encargado de Laboratorio'];
        }

        return ['Administrador'];
    }

    public static function forRole(string $role): array
    {
        return array_values(array_filter(array_keys(self::DEFINITIONS), fn ($code) => in_array($role, self::eligibleRoles($code), true)));
    }

    // Required to recover management; ordinary admin permissions remain removable.
    public const RECOVERY = ['usuarios.ver', 'usuarios.crear', 'roles.ver', 'permisos.ver', 'roles.gestionar_permisos', 'roles.asignar'];
}
