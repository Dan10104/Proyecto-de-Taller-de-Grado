<?php

namespace App\Services\Notifications;

use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NotificationReadService
{
    public function mark(Usuario $user, int $id): Notificacion
    {
        Gate::forUser($user->fresh())->authorize('notificaciones.ver_propias');
        $query = Notificacion::where('destinatario_id', $user->id)->whereKey($id);
        (clone $query)->firstOrFail();
        // A current conditional write serializes simultaneous readers and preserves the winner.
        DB::table('notificaciones')->where('id', $id)->where('destinatario_id', $user->id)->whereNull('leida_en')->update(['leida_en' => now()->toDateTimeString()]);

        return $query->firstOrFail();
    }

    public function unread(Usuario $user): int
    {
        return Notificacion::where('destinatario_id', $user->id)->whereNull('leida_en')->count();
    }
}
