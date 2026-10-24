<?php

namespace App\Services;

use App\Exceptions\ReleaseDeferredException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReleaseScanService
{
    public function __construct(private ReleaseEvaluationService $evaluator) {}

    public function candidates(CarbonImmutable $time): Builder
    {
        return DB::table('reservas')->where('estado', 'APROBADA')->where('inicio', '<=', $time)->where('fin', '>', $time)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('espacios_academicos')->whereColumn('recurso_id', 'reservas.espacio_id')->whereNotNull('politica_liberacion_id'));
    }

    public function run(): array
    {
        $counts = ['candidatas' => 0, 'evaluadas' => 0, 'omitidas' => 0, 'diferidas' => 0, 'errores' => 0];
        $size = max(1, min(1000, (int) config('release.batch_size', 100)));
        $this->candidates(CarbonImmutable::now('America/La_Paz')->startOfSecond())->select('id')->chunkById($size, function ($rows) use (&$counts) {
            foreach ($rows as $row) {
                $counts['candidatas']++;
                try {
                    $counts[$this->evaluator->evaluate($row->id) ? 'evaluadas' : 'omitidas']++;
                } catch (ReleaseDeferredException) {
                    $counts['diferidas']++;
                    Log::notice('CU23 diferido por concurrencia o frontera temporal.', ['reserva_id' => $row->id]);
                } catch (Throwable) {
                    $counts['errores']++;
                    // Never log SQL, bindings, request payloads or exception messages.
                    Log::error('CU23 no pudo evaluar la reserva; la transacción fue revertida.', ['reserva_id' => $row->id]);
                }
            }
        });

        return $counts;
    }
}
