<?php

namespace App\Http\Requests;

class IncidentFilterRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('incidencias.ver');
    }

    public function rules(): array
    {
        return ['q' => 'nullable|string|max:160', 'recurso_id' => 'nullable|integer|min:1', 'estado' => 'nullable|in:ABIERTA,CERRADA', 'tipo' => 'nullable|in:INCIDENCIA,MANTENIMIENTO', 'page' => 'nullable|integer|min:1'];
    }
}
