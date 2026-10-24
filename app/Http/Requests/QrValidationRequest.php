<?php

namespace App\Http\Requests;

use App\Models\CodigoQr;
use App\Services\QrTokenService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class QrValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($reservation = $this->route('reserva')) {
            return $this->user()->can('useSpace', [CodigoQr::class, $reservation]);
        }

        return $this->user()->can('qr.validar_operacion') && $this->user()->can('prestamos.ver_administrativos')
            && $this->user()->can($this->route()->getActionMethod() === 'deliver' ? 'prestamos.registrar_entrega' : 'prestamos.registrar_devolucion');
    }

    protected function prepareForValidation(): void
    {
        if ($this->query->has('qr_token')) {
            throw ValidationException::withMessages(['qr_token' => 'La lectura debe enviarse en el cuerpo de la petición.']);
        }
        $payload = $this->input('qr_token');
        $this->merge(OperationalRequest::normalize($this->except('qr_token')));
        $this->merge(['qr_token' => $payload]);
    }

    public function rules(): array
    {
        $base = ['qr_token' => ['present', function ($attribute, $value, $fail) {
            try {
                app(QrTokenService::class)->admit($value);
            } catch (ValidationException) {
                $fail('Se requiere una lectura QR de entre 1 y 1024 bytes.');
            }
        }]] + OperationalRequest::protectedRules(['validacion_id', 'codigo_qr_id', 'hash_leido', 'token_hash', 'recurso_esperado_id', 'validado_por_id', 'fecha_hora', 'resultado', 'tipo_operacion', 'entrega_id', 'devolucion_id', 'reserva_id', 'prestamo_id']);

        return $base + ($this->route('reserva') ? [] : LoanActionRequest::actionRules($this->route()->getActionMethod()));
    }
}
