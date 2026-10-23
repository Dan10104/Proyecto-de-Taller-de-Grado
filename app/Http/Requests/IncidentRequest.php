<?php

namespace App\Http\Requests;

class IncidentRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route()->getActionMethod() === 'close' ? 'incidencias.cerrar' : 'incidencias.gestionar');
    }

    public function rules(): array
    {
        return $this->route()->getActionMethod() === 'close' ? self::closeRules() : self::inputRules();
    }

    public static function inputRules(): array
    {
        return ['recurso_id' => 'required|integer|exists:recursos_institucionales,id', 'tipo' => 'required|in:INCIDENCIA,MANTENIMIENTO', 'descripcion' => 'required|string|max:16000', 'inicio' => self::dateRules(), 'fin_previsto' => 'nullable|date_format:Y-m-d\TH:i|after:inicio|before_or_equal:9999-12-31T23:59', 'estado_espacio' => 'sometimes|in:EN_MANTENIMIENTO,BLOQUEADO', ...self::protectedRules(['id', 'reportado_por_id', 'cerrado_por_id', 'devolucion_id', 'cerrada_en', 'solucion', 'estado'])];
    }

    public static function closeRules(): array
    {
        return ['solucion' => 'required|string|max:16000', 'apto' => 'sometimes|boolean', ...self::protectedRules(['id', 'recurso_id', 'reportado_por_id', 'cerrado_por_id', 'devolucion_id', 'cerrada_en', 'estado', 'tipo', 'descripcion', 'inicio', 'fin_previsto'])];
    }
}
