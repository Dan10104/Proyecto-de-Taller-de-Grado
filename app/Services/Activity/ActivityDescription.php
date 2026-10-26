<?php

namespace App\Services\Activity;

use App\Enums\ActivityAction;
use App\Services\Reporting\ReportRegistry;
use InvalidArgumentException;

final class ActivityDescription
{
    private const NUMBERS = ['id', 'anterior_id', 'nuevo_id', 'asignacion_id', 'cantidad', 'anadidos', 'retirados', 'sesiones_revocadas'];

    private const FIELDS = ['nombres', 'apellidos', 'correo', 'password', 'descripcion', 'nombre', 'codigo', 'numero', 'piso_id', 'tipo_espacio_id', 'capacidad', 'referencia_ubicacion', 'estado', 'caracteristicas', 'tipo_equipamiento_id', 'espacio_base_id', 'marca', 'modelo', 'numero_serie', 'tipo', 'espacio_actual_id'];

    private const STATES = ['ACTIVO', 'INACTIVO', 'DISPONIBLE', 'BLOQUEADO', 'EN_MANTENIMIENTO', 'PRESTADO', 'BAJA', 'HABILITADO', 'DESHABILITADO'];

    public static function make(ActivityAction $action, array $facts = []): string
    {
        if ($action->legacy()) {
            throw new InvalidArgumentException('Los hitos existentes conservan su plantilla.');
        }
        $parts = [$action->label().'.'];
        ksort($facts);
        foreach ($facts as $key => $value) {
            if (in_array($key, self::NUMBERS, true) && is_int($value) && $value >= 0) {
                $parts[] = $key.'='.$value;
            } elseif ($key === 'campos' && is_array($value) && ! array_diff($value, self::FIELDS)) {
                sort($value);
                $parts[] = 'campos='.implode(',', array_unique($value));
            } elseif (in_array($key, ['anterior', 'nuevo'], true) && in_array($value, self::STATES, true)) {
                $parts[] = $key.'='.$value;
            } elseif (in_array($key, ['inicio', 'fin'], true) && is_string($value) && preg_match('/\A\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\z/D', $value)) {
                $parts[] = $key.'='.$value;
            } else {
                throw new InvalidArgumentException('La plantilla no admite datos libres.');
            }
        }
        $text = implode('; ', $parts);
        if (mb_strlen($text) > 500) {
            throw new InvalidArgumentException('Plantilla demasiado extensa.');
        }

        return $text;
    }

    public static function failure(bool $rejected): string
    {
        return $rejected ? 'Operación rechazada por una guarda funcional.' : 'Operación no confirmada; negocio revertido.';
    }

    public static function safe(ActivityAction $action, ?string $text): ?string
    {
        if ($text === null || mb_strlen($text) > 500) {
            return null;
        }
        if (! $action->legacy()) {
            if (in_array($text, [self::failure(true), self::failure(false)], true)) {
                return $text;
            }
            $parts = explode('; ', $text);
            if (array_shift($parts) !== $action->label().'.') {
                return null;
            }
            $facts = [];
            foreach ($parts as $part) {
                $pair = explode('=', $part, 2);
                if (count($pair) !== 2 || isset($facts[$pair[0]])) {
                    return null;
                }
                [$key, $value] = $pair;
                $facts[$key] = in_array($key, self::NUMBERS, true) && ctype_digit($value) ? (int) $value : ($key === 'campos' ? ($value === '' ? [] : explode(',', $value)) : $value);
            }
            try {
                return self::make($action, $facts) === $text ? $text : null;
            } catch (InvalidArgumentException) {
                return null;
            }
        }
        if (in_array($action, [ActivityAction::RESERVA_ALERTADA_LIBERACION, ActivityAction::RESERVA_LIBERADA], true)) {
            return preg_match('/\AEvaluación CU23 #[1-9]\d*; política #[1-9]\d*\.\z/uD', $text) ? $text : null;
        }

        if ($action === ActivityAction::REPORTE_EXPORTADO) {
            return self::exportDescription($text);
        }

        return null;
    }

    private static function exportDescription(string $text): ?string
    {
        if (! preg_match('/\AReporte ([a-z-]+); formato (pdf|xlsx); filas ([1-9]\d*); corte (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}); filtros (\{.*\}|\[\])\z/uD', $text, $matches)) {
            return null;
        }
        $definition = app(ReportRegistry::class)->definitions()[$matches[1]] ?? null;
        if (! $definition) {
            return null;
        }
        try {
            $filters = json_decode($matches[5], true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (! is_array($filters)) {
            return null;
        }
        $specs = $definition->filters;
        if ($definition->dated) {
            $specs += ['periodo' => ['hoy', '7_dias', '30_dias', 'personalizado'], 'fecha_inicio' => 'date', 'fecha_fin' => 'date'];
        }
        foreach ($filters as $key => $value) {
            $spec = $specs[$key] ?? null;
            $valid = is_array($spec) ? in_array($value, $spec, true) : match ($spec) {
                'reference' => is_int($value) && $value > 0,
                'date' => is_string($value) && preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value),
                'instant' => is_string($value) && preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/D', $value),
                default => false,
            };
            if (! $valid) {
                return null;
            }
        }

        return $text;
    }
}
