<?php

namespace App\Http\Requests;

class LoanRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('prestamos.solicitar');
    }

    public function rules(): array
    {
        return self::inputRules();
    }

    public static function serverFields(): array
    {
        return ['id', 'codigo', 'solicitante_id', 'resuelto_por_id', 'solicitado_en', 'estado', 'resuelto_en', 'motivo_rechazo', 'entregado_por_id', 'receptor_id', 'fecha_hora', 'recepcion_confirmada_en', 'recibido_por_id', 'confirmado_por_id', 'registrada_en', 'confirmada_en', 'devolucion_id', 'prestamo_id'];
    }

    public static function inputRules(): array
    {
        return ['equipamiento_id' => 'required|integer|exists:equipamientos_tecnologicos,recurso_id', 'inicio_previsto' => self::dateRules(), 'fin_previsto' => [...self::dateRules(), 'after:inicio_previsto'], 'motivo' => 'required|string|max:500', ...self::protectedRules(self::serverFields())];
    }
}
