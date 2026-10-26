<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\DB;

class OverdueLoanService
{
    public function detect(int $id): bool
    {
        if (DB::transactionLevel() !== 0) {
            throw new \LogicException('La detección requiere una transacción nueva.');
        }

        return DB::transaction(function () use ($id) {
            $p = DB::table('prestamos')->where('id', $id)->lockForUpdate()->first();
            if (! $p || $p->estado !== 'ENTREGADO' || now()->toDateTimeString() <= $p->fin_previsto) {
                return false;
            }
            if (DB::table('registros_actividad')->where('prestamo_id', $id)->where('accion', 'PRESTAMO_VENCIDO')->exists()) {
                return false;
            }
            app(NotificationActivityRecorder::class)->loan($id, 'PRESTAMO_VENCIDO', null);

            return true;
        }, 3);
    }
}
