<?php

namespace App\Services\Notifications;

use App\Models\RegistroActividad;
use App\Support\PermissionCatalog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class NotificationRecipients
{
    /** id => whether the direct template takes precedence. All reads share the generation snapshot. */
    public function resolve(RegistroActividad $activity, string $strategy, bool $administrativeOpen): array
    {
        $selfCancel = $strategy === 'CANCELACION' && $activity->actor_id === $activity->usuario_afectado_id;
        $direct = $strategy === 'S' || str_starts_with($strategy, 'S_') || ($strategy === 'CANCELACION' && ! $selfCancel);
        $group = match (true) {
            $selfCancel => 'AR',
            str_starts_with($strategy, 'S_') => substr($strategy, 2),
            isset(NotificationRuleRegistry::GROUPS[$strategy]) => $strategy,
            default => null,
        };
        $recipients = [];
        if ($administrativeOpen && $group !== null) {
            $query = DB::table('usuarios')->where('estado', 'ACTIVO');
            if ($activity->actor_id !== null) {
                $query->where('id', '!=', $activity->actor_id);
            }
            foreach (NotificationRuleRegistry::GROUPS[$group] as $code) {
                $query->whereExists(function (Builder $q) use ($code) {
                    $q->selectRaw('1')->from('asignaciones_roles as ar')
                        ->join('roles as r', 'r.id', '=', 'ar.rol_id')
                        ->join('roles_permisos as rp', 'rp.rol_id', '=', 'r.id')
                        ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
                        ->whereColumn('ar.usuario_id', 'usuarios.id')->whereNull('ar.fecha_retiro')
                        ->where('p.codigo', $code)->whereIn('r.nombre', PermissionCatalog::eligibleRoles($code));
                });
            }
            foreach ($query->orderBy('id')->pluck('id') as $id) {
                $recipients[$id] = false;
            }
        }
        if ($direct && $activity->usuario_afectado_id !== null && DB::table('usuarios')->where('id', $activity->usuario_afectado_id)->where('estado', 'ACTIVO')->exists()) {
            $recipients[$activity->usuario_afectado_id] = true;
        }
        ksort($recipients, SORT_NUMERIC);

        return $recipients;
    }
}
