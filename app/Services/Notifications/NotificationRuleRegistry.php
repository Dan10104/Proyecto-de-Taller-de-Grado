<?php

namespace App\Services\Notifications;

use App\Enums\TipoNotificacion;

class NotificationRuleRegistry
{
    public const GROUPS = [
        'AR' => ['reservas.resolver', 'reservas.ver_administrativas'],
        'AP' => ['prestamos.resolver', 'prestamos.ver_administrativos'],
        'AD' => ['prestamos.registrar_devolucion', 'prestamos.ver_administrativos'],
        'AI' => ['incidencias.ver'],
        'AL' => ['liberacion.evaluaciones.ver_administrativas'],
    ];

    // type, recipient strategy, title, direct message, administrative message.
    public const RULES = [
        'RESERVA_SOLICITADA' => ['SOLICITUD', 'AR', 'Nueva solicitud de reserva', null, 'La solicitud de reserva {reserva} fue registrada y requiere resolución.'],
        'RESERVA_APROBADA' => ['DECISION', 'S', 'Reserva aprobada', 'Tu solicitud de reserva {reserva} fue aprobada.', null],
        'RESERVA_RECHAZADA' => ['DECISION', 'S', 'Reserva rechazada', 'Tu solicitud de reserva {reserva} fue rechazada. Consulta el detalle autorizado para más información.', null],
        'RESERVA_CANCELADA' => ['CANCELACION', 'CANCELACION', 'Reserva cancelada', 'Tu reserva {reserva} fue cancelada por un responsable autorizado.', 'El titular canceló la reserva {reserva}.'],
        'PRESTAMO_SOLICITADO' => ['SOLICITUD', 'AP', 'Nueva solicitud de préstamo', null, 'La solicitud de préstamo {prestamo} fue registrada y requiere resolución.'],
        'PRESTAMO_APROBADO' => ['DECISION', 'S', 'Préstamo aprobado', 'Tu solicitud de préstamo {prestamo} fue aprobada.', null],
        'PRESTAMO_RECHAZADO' => ['DECISION', 'S', 'Préstamo rechazado', 'Tu solicitud de préstamo {prestamo} fue rechazada. Consulta el detalle autorizado para más información.', null],
        'PRESTAMO_ENTREGADO' => ['ENTREGA', 'S', 'Equipamiento entregado', 'Se confirmó la entrega del equipamiento de tu préstamo {prestamo}.', null],
        'DEVOLUCION_PENDIENTE_REVISION' => ['DEVOLUCION', 'AD', 'Devolución pendiente de revisión', null, 'La recepción del préstamo {prestamo} quedó pendiente de revisión de su condición física.'],
        'PRESTAMO_DEVUELTO' => ['DEVOLUCION', 'S', 'Devolución confirmada', 'Se confirmó la devolución de tu préstamo {prestamo}.', null],
        'PRESTAMO_VENCIDO' => ['VENCIMIENTO', 'S_AD', 'Préstamo vencido', 'Tu préstamo {prestamo} fue detectado como vencido.', 'El préstamo {prestamo} fue detectado como vencido y requiere seguimiento.'],
        'INCIDENCIA_REGISTRADA' => ['INCIDENCIA', 'S_AI', 'Incidencia registrada', 'El equipamiento de tu préstamo {prestamo} requiere revisión; se registró una incidencia.', 'Se registró la incidencia #{incidencia}; consulta el detalle autorizado.'],
        'INCIDENCIA_CERRADA' => ['INCIDENCIA', 'S_AI', 'Incidencia cerrada', 'Se cerró la incidencia vinculada a la devolución de tu préstamo {prestamo}.', 'Se cerró la incidencia #{incidencia}.'],
        'MANTENIMIENTO_REGISTRADO' => ['INCIDENCIA', 'S_AI', 'Mantenimiento registrado', 'El equipamiento de tu préstamo {prestamo} requiere revisión; se registró mantenimiento.', 'Se registró el mantenimiento #{incidencia}.'],
        'MANTENIMIENTO_CERRADO' => ['INCIDENCIA', 'S_AI', 'Mantenimiento cerrado', 'Se cerró el mantenimiento vinculado a la devolución de tu préstamo {prestamo}.', 'Se cerró el mantenimiento #{incidencia}.'],
        'RESERVA_ALERTADA_LIBERACION' => ['LIBERACION', 'S_AL', 'Reserva requiere atención', 'Se detectó posible no utilización de tu reserva {reserva}; requiere atención. Esta alerta no liberó la reserva.', 'La reserva {reserva} presenta posible no utilización; requiere atención. Esta alerta no liberó la reserva.'],
        'RESERVA_LIBERADA' => ['LIBERACION', 'S_AL', 'Reserva liberada', 'Tu reserva {reserva} fue liberada conforme a la política aplicable.', 'La reserva {reserva} fue liberada conforme a la política aplicable.'],
    ];

    public function rule(string $action): ?array
    {
        if (! isset(self::RULES[$action])) {
            return null;
        }
        [$type, $strategy, $title, $direct, $administrative] = self::RULES[$action];

        return ['tipo' => TipoNotificacion::from($type), 'strategy' => $strategy, 'titulo' => $title, 'direct' => $direct, 'administrative' => $administrative];
    }

    public function template(array $rule, array $context, bool $direct): array
    {
        $template = $rule[$direct ? 'direct' : 'administrative'];
        if (! is_string($template) || mb_strlen($rule['titulo']) > 160) {
            throw new \LogicException('Plantilla de notificación inválida.');
        }
        $message = strtr($template, $context).' Evento registrado: '.$context['{fecha_evento}'].' (America/La_Paz).';
        if (preg_match('/\{[a-z_]+\}/', $message)) {
            throw new \LogicException('Contexto de plantilla incompleto.');
        }

        return ['titulo' => $rule['titulo'], 'mensaje' => $message];
    }
}
