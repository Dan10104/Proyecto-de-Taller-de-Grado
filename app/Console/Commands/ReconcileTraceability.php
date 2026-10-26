<?php

namespace App\Console\Commands;

use App\Enums\AccionCritica;
use App\Jobs\RegisterTraceabilityEvidence;
use App\Models\EvidenciaTrazabilidad;
use App\Models\RegistroActividad;
use App\Services\Traceability\LocalEvidenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class ReconcileTraceability extends Command
{
    protected $signature = 'trazabilidad:reconciliar';

    protected $description = 'Reconstruye evidencias locales faltantes y reconcilia registros pendientes de Sepolia.';

    public function handle(LocalEvidenceService $local): int
    {
        $batch = max(1, min(100, config('blockchain.batch_size')));
        $created = 0;
        // Rotating high-water marks prevent one unavailable history/provider from starving later rows.
        foreach (['activity', 'evidence'] as $kind) {
            $cursor = (int) Cache::get('traceability:cursor:'.$kind, 0);
            $query = $kind === 'activity'
                ? RegistroActividad::where('critica', true)->where('resultado', 'EXITO')->whereIn('accion', array_column(AccionCritica::cases(), 'value'))->whereDoesntHave('evidencia')
                : EvidenciaTrazabilidad::where('estado', 'PENDIENTE');
            $rows = (clone $query)->where('id', '>', $cursor)->orderBy('id')->limit($batch)->get();
            if ($rows->isEmpty()) {
                $rows = $query->orderBy('id')->limit($batch)->get();
            }
            foreach ($rows as $row) {
                try {
                    if ($kind === 'activity') {
                        $local->ensure($row->id);
                        $created++;
                    } else {
                        app()->call([new RegisterTraceabilityEvidence($row->id), 'handle']);
                    }
                } catch (\Throwable) {
                    // Intentionally omit exception/provider text and canonical data.
                }
                $cursor = $row->id;
            }
            Cache::put('traceability:cursor:'.$kind, $cursor, 86400);
        }
        $this->info('Reconciliación terminada. Evidencias locales creadas: '.$created.'.');

        return self::SUCCESS;
    }
}
