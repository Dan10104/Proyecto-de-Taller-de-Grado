<?php

namespace App\Http\Requests;

class LoanFilterRequest extends OperationalRequest
{
    public function administrative(): bool
    {
        return ($this->input('alcance') ?? ($this->user()->can('prestamos.ver_administrativos') ? 'administrativos' : 'propios')) === 'administrativos';
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->administrative() ? 'prestamos.ver_administrativos' : 'prestamos.ver_propios');
    }

    public function rules(): array
    {
        return ['alcance' => 'nullable|in:propios,administrativos', 'estado' => 'nullable|in:PENDIENTE,APROBADO,RECHAZADO,ENTREGADO,DEVUELTO', 'equipamiento_id' => 'nullable|integer|min:1', 'solicitante_id' => $this->administrative() ? 'nullable|integer|min:1' : 'prohibited', 'fecha' => 'nullable|date_format:Y-m-d|after_or_equal:1000-01-01|before_or_equal:9999-12-31', 'vencidos' => 'nullable|boolean', 'revision' => 'nullable|boolean', 'q' => 'nullable|string|max:160', 'page' => 'nullable|integer|min:1'];
    }
}
