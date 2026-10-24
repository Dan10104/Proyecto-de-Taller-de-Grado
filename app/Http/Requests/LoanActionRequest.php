<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class LoanActionRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(match ($this->route()->getActionMethod()) {
            'approve','reject' => 'prestamos.resolver', 'deliver' => 'prestamos.registrar_entrega', default => 'prestamos.registrar_devolucion'
        });
    }

    public function rules(): array
    {
        return self::actionRules($this->route()->getActionMethod());
    }

    public static function actionRules(string $action): array
    {
        $protected = LoanRequest::serverFields();
        if ($action === 'reject') {
            $protected = array_diff($protected, ['motivo_rechazo']);
        }
        $rules = self::protectedRules([...$protected, 'qr_token', 'validacion_id', 'equipamiento_id', 'inicio_previsto', 'fin_previsto', 'motivo']);

        return $rules + match ($action) {
            'approve' => [],
            'reject' => ['motivo_rechazo' => 'required|string|max:500'],
            'deliver' => ['condicion_entrega' => 'required|string|max:255', 'observacion' => 'nullable|string|max:500', 'equipo_verificado' => 'required|accepted', 'receptor_verificado' => 'required|accepted'],
            'receive','review' => ['condicion' => ['required', Rule::in($action === 'review' ? ['ADECUADA', 'CON_INCIDENCIA'] : ['ADECUADA', 'CON_INCIDENCIA', 'NO_DETERMINABLE'])], 'descripcion_condicion' => 'required_if:condicion,CON_INCIDENCIA,NO_DETERMINABLE|nullable|string|max:500', 'observacion' => 'nullable|string|max:500', 'apto' => 'sometimes|boolean'],
            default => []
        };
    }
}
