<?php

namespace App\Services\Reporting;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class ReportPeriod
{
    public function __construct(public CarbonImmutable $start, public CarbonImmutable $end, public string $preset) {}

    public static function from(array $filters, CarbonImmutable $now): self
    {
        $preset = $filters['periodo'] ?? 'hoy';
        Validator::make(['periodo' => $preset], ['periodo' => ['required', Rule::in(['hoy', '7_dias', '30_dias', 'personalizado'])]])->validate();
        if ($preset === 'personalizado') {
            Validator::make($filters, [
                'fecha_inicio' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-30'],
                'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio', 'before_or_equal:9999-12-30'],
            ])->validate();

            return new self(CarbonImmutable::parse($filters['fecha_inicio'], 'America/La_Paz')->startOfDay(), CarbonImmutable::parse($filters['fecha_fin'], 'America/La_Paz')->addDay()->startOfDay(), $preset);
        }
        if (isset($filters['fecha_inicio']) || isset($filters['fecha_fin'])) {
            throw ValidationException::withMessages(['periodo' => 'Las fechas explícitas requieren el periodo Personalizado.']);
        }
        $days = match ($preset) {
            '7_dias' => 7, '30_dias' => 30, default => 1
        };

        return new self($now->startOfDay()->subDays($days - 1), $now->startOfDay()->addDay(), $preset);
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end);
    }

    public function monthly(): bool
    {
        return $this->days() > 31;
    }

    public function filters(): array
    {
        return ['periodo' => $this->preset, 'fecha_inicio' => $this->start->toDateString(), 'fecha_fin' => $this->end->subDay()->toDateString()];
    }

    public function buckets(): array
    {
        // Bound chart points before allocating a huge array/DOM for extreme date input.
        $points = $this->monthly() ? (int) $this->start->startOfMonth()->diffInMonths($this->end->subMicrosecond()->startOfMonth()) + 1 : $this->days();
        if ($points > max(1, (int) config('reporting.dashboard_max_buckets', 1200))) {
            throw ValidationException::withMessages(['periodo' => 'El rango genera demasiados puntos para el gráfico. Reduzca el periodo.']);
        }
        $buckets = [];
        $cursor = $this->monthly() ? $this->start->startOfMonth() : $this->start;
        while ($cursor->lessThan($this->end)) {
            $buckets[$cursor->format($this->monthly() ? 'Y-m' : 'Y-m-d')] = 0;
            $cursor = $this->monthly() ? $cursor->addMonth() : $cursor->addDay();
        }

        return $buckets;
    }
}
