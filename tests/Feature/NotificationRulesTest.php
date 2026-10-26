<?php

namespace Tests\Feature;

use App\Models\AsignacionRol;
use App\Models\Notificacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Services\Notifications\NotificationRuleRegistry;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\NotificationTestCase;

#[Group('notifications')]
class NotificationRulesTest extends NotificationTestCase
{
    public static function rules(): array
    {
        // Independent expectations from N3/N7; no reading the registry to generate expected values.
        return [
            ['RESERVA_SOLICITADA', 'SOLICITUD', 'AR', 'Nueva solicitud de reserva', null, 'La solicitud de reserva {reserva} fue registrada y requiere resolución.'],
            ['RESERVA_APROBADA', 'DECISION', 'S', 'Reserva aprobada', 'Tu solicitud de reserva {reserva} fue aprobada.', null],
            ['RESERVA_RECHAZADA', 'DECISION', 'S', 'Reserva rechazada', 'Tu solicitud de reserva {reserva} fue rechazada. Consulta el detalle autorizado para más información.', null],
            ['RESERVA_CANCELADA', 'CANCELACION', 'CANCELACION', 'Reserva cancelada', 'Tu reserva {reserva} fue cancelada por un responsable autorizado.', 'El titular canceló la reserva {reserva}.'],
            ['PRESTAMO_SOLICITADO', 'SOLICITUD', 'AP', 'Nueva solicitud de préstamo', null, 'La solicitud de préstamo {prestamo} fue registrada y requiere resolución.'],
            ['PRESTAMO_APROBADO', 'DECISION', 'S', 'Préstamo aprobado', 'Tu solicitud de préstamo {prestamo} fue aprobada.', null],
            ['PRESTAMO_RECHAZADO', 'DECISION', 'S', 'Préstamo rechazado', 'Tu solicitud de préstamo {prestamo} fue rechazada. Consulta el detalle autorizado para más información.', null],
            ['PRESTAMO_ENTREGADO', 'ENTREGA', 'S', 'Equipamiento entregado', 'Se confirmó la entrega del equipamiento de tu préstamo {prestamo}.', null],
            ['DEVOLUCION_PENDIENTE_REVISION', 'DEVOLUCION', 'AD', 'Devolución pendiente de revisión', null, 'La recepción del préstamo {prestamo} quedó pendiente de revisión de su condición física.'],
            ['PRESTAMO_DEVUELTO', 'DEVOLUCION', 'S', 'Devolución confirmada', 'Se confirmó la devolución de tu préstamo {prestamo}.', null],
            ['PRESTAMO_VENCIDO', 'VENCIMIENTO', 'S_AD', 'Préstamo vencido', 'Tu préstamo {prestamo} fue detectado como vencido.', 'El préstamo {prestamo} fue detectado como vencido y requiere seguimiento.'],
            ['INCIDENCIA_REGISTRADA', 'INCIDENCIA', 'S_AI', 'Incidencia registrada', 'El equipamiento de tu préstamo {prestamo} requiere revisión; se registró una incidencia.', 'Se registró la incidencia #{incidencia}; consulta el detalle autorizado.'],
            ['INCIDENCIA_CERRADA', 'INCIDENCIA', 'S_AI', 'Incidencia cerrada', 'Se cerró la incidencia vinculada a la devolución de tu préstamo {prestamo}.', 'Se cerró la incidencia #{incidencia}.'],
            ['MANTENIMIENTO_REGISTRADO', 'INCIDENCIA', 'S_AI', 'Mantenimiento registrado', 'El equipamiento de tu préstamo {prestamo} requiere revisión; se registró mantenimiento.', 'Se registró el mantenimiento #{incidencia}.'],
            ['MANTENIMIENTO_CERRADO', 'INCIDENCIA', 'S_AI', 'Mantenimiento cerrado', 'Se cerró el mantenimiento vinculado a la devolución de tu préstamo {prestamo}.', 'Se cerró el mantenimiento #{incidencia}.'],
            ['RESERVA_ALERTADA_LIBERACION', 'LIBERACION', 'S_AL', 'Reserva requiere atención', 'Se detectó posible no utilización de tu reserva {reserva}; requiere atención. Esta alerta no liberó la reserva.', 'La reserva {reserva} presenta posible no utilización; requiere atención. Esta alerta no liberó la reserva.'],
            ['RESERVA_LIBERADA', 'LIBERACION', 'S_AL', 'Reserva liberada', 'Tu reserva {reserva} fue liberada conforme a la política aplicable.', 'La reserva {reserva} fue liberada conforme a la política aplicable.'],
        ];
    }

    #[DataProvider('rules')]
    public function test_all_seventeen_types_templates_and_exact_recipient_sets(string $action, string $type, string $strategy, string $title, ?string $direct, ?string $administrative): void
    {
        $subject = $this->user('Docente');
        $actor = $this->user('Administrador');
        $admin = $this->user('Administrador');
        $inactive = $this->user('Administrador');
        $inactive->forceFill(['estado' => 'INACTIVO'])->save();
        $retired = $this->user('Administrador');
        DB::table('asignaciones_roles')->where('usuario_id', $retired->id)->update(['fecha_retiro' => now()]);
        $outsider = $this->user('Estudiante');
        // A rogue permission on an ineligible role must not grant administrative scope.
        Rol::where('nombre', 'Estudiante')->sole()->permisos()->syncWithoutDetaching(Permiso::pluck('id')->all());
        $a = $this->activity($action, $subject, $actor);
        $rule = app(NotificationRuleRegistry::class)->rule($action);
        $this->assertSame($strategy, $rule['strategy']);
        $this->assertSame($type, $rule['tipo']->value);
        $isDirect = $strategy === 'S' || $strategy === 'CANCELACION' || str_starts_with($strategy, 'S_');
        $isAdmin = in_array($strategy, ['AR', 'AP', 'AD', 'S_AD', 'S_AI', 'S_AL']);
        $expected = $isDirect ? [$subject->id] : [];
        if ($isAdmin) {
            $expected[] = $admin->id;
            if ($a->actor_id !== $actor->id) {
                $expected[] = $actor->id;
            }
        }
        $this->assertSame(count($expected), $this->generate($a));
        $rows = Notificacion::where('registro_actividad_id', $a->id)->get();
        $this->assertEqualsCanonicalizing($expected, $rows->pluck('destinatario_id')->all());
        $replace = ['{reserva}' => $a->reserva?->codigo ?? '', '{prestamo}' => $a->prestamo?->codigo ?? '', '{incidencia}' => (string) $a->incidencia_id];
        foreach ($rows as $n) {
            $this->assertSame($title, $n->titulo);
            $this->assertSame($type, $n->tipo->value);
            $this->assertSame(strtr($n->destinatario_id === $subject->id ? $direct : $administrative, $replace).' Evento registrado: 2090-01-10 08:15:00 (America/La_Paz).', $n->mensaje);
            $this->assertNull($n->leida_en);
            $this->assertLessThanOrEqual(160, mb_strlen($n->titulo));
        }
        $this->assertSame(0, $this->generate($a));
        $this->assertCount(17, NotificationRuleRegistry::RULES);
    }

    public function test_self_cancellation_excludes_owner_even_with_multiple_admin_roles(): void
    {
        $owner = $this->user('Administrador');
        $other = $this->user('Administrador');
        $a = $this->activity('RESERVA_CANCELADA', $owner, $owner);
        $this->assertSame(1, $this->generate($a));
        $n = Notificacion::sole();
        $this->assertSame($other->id, $n->destinatario_id);
        $this->assertStringStartsWith('El titular canceló', $n->mensaje);
    }

    public function test_direct_union_wins_and_does_not_require_view_permission(): void
    {
        $owner = $this->user('Administrador');
        $actor = $this->user('Administrador');
        (new AsignacionRol)->forceFill(['usuario_id' => $owner->id, 'rol_id' => Rol::where('nombre', 'Encargado de Laboratorio')->sole()->id, 'asignado_por_id' => $actor->id, 'fecha_asignacion' => now()])->save();
        DB::table('roles_permisos')->where('permiso_id', Permiso::where('codigo', 'notificaciones.ver_propias')->sole()->id)->delete();
        $a = $this->activity('PRESTAMO_VENCIDO', $owner, $actor);
        $this->assertSame(2, $this->generate($a));
        $this->assertStringStartsWith('Tu préstamo', Notificacion::where('destinatario_id', $owner->id)->sole()->mensaje);
        $this->assertFalse($owner->can('notificaciones.ver_propias'));
    }

    public function test_partial_permissions_and_revoked_roles_do_not_resolve_admin_recipient(): void
    {
        $subject = $this->user('Docente');
        $admin = $this->user('Administrador');
        $a = $this->activity('RESERVA_SOLICITADA', $subject, $admin);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'reservas.ver_administrativas')->sole());
        $this->assertSame(0, $this->generate($a));
        Rol::where('nombre', 'Administrador')->sole()->permisos()->attach(Permiso::where('codigo', 'reservas.ver_administrativas')->sole());
        $this->assertSame(1, $this->generate($a));
    }
}
