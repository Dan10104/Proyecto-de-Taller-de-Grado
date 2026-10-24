<?php

namespace App\Services;

use App\Enums\ResultadoValidacionQr;
use App\Enums\TipoOperacionQr;
use App\Exceptions\EquipmentConflictException;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\LoanActionRequest;
use App\Http\Requests\LoanRequest;
use App\Models\CodigoQr;
use App\Models\EspacioAcademico;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Models\ValidacionQr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class QrValidationService
{
    public function __construct(private QrTokenService $tokens, private QrContextService $contexts,
        private SpaceAvailabilityService $spaces, private ResourceOperationService $operations, private LoanService $loans) {}

    public function useSpace(Usuario $actor, Reserva $target, #[\SensitiveParameter] mixed $payload, ?string $sessionHash = null): ValidacionQr
    {
        $payload = $this->tokens->admit($payload);

        return $this->contexts->transaction($actor, 'qr.validar_uso_espacio', function ($fresh) use ($target, $payload) {
            Gate::forUser($fresh)->authorize('useSpace', [CodigoQr::class, $target]);
            $space = EspacioAcademico::whereKey($target->espacio_id)->lockForUpdate()->firstOrFail();
            $reservation = Reserva::whereKey($target->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($fresh)->authorize('useSpace', [CodigoQr::class, $reservation]);
            abort_unless($reservation->espacio_id === $space->getKey(), 409);
            // Acquire all availability dependencies before QR; retain its canonical predicate.
            $restriction = null;
            try {
                $this->spaces->assertAvailable($space, $reservation->inicio, $reservation->fin, $reservation->id);
            } catch (ReservationConflictException $error) {
                $restriction = $error->getMessage();
            }
            [$code, $reason, $now] = $this->check($payload, TipoOperacionQr::USO_ESPACIO, $space->getKey(), $reservation->id);
            $reason ??= $reservation->estado->value !== 'APROBADA' ? 'La reserva no está aprobada.' : null;
            $reason ??= $now->lt($reservation->inicio) || ! $now->lt($reservation->fin) ? 'La reserva está fuera de su periodo autorizado.' : null;
            $reason ??= $restriction;

            return $this->record($fresh, $payload, $code, TipoOperacionQr::USO_ESPACIO, $space->getKey(), $reservation->id, $now, $reason);
        }, $sessionHash);
    }

    public function operation(Usuario $actor, Prestamo $target, TipoOperacionQr $type, #[\SensitiveParameter] mixed $payload, array $data, ?string $sessionHash = null): ValidacionQr
    {
        abort_unless(in_array($type, [TipoOperacionQr::ENTREGA, TipoOperacionQr::DEVOLUCION], true), 422);
        $payload = $this->tokens->admit($payload);
        $delivery = $type === TipoOperacionQr::ENTREGA;
        $data = LoanRequest::normalize($data);
        Validator::make($data, LoanActionRequest::actionRules($delivery ? 'deliver' : 'receive'))->validate();

        return $this->contexts->transaction($actor, 'qr.validar_operacion', function ($fresh) use ($target, $type, $payload, $data, $delivery) {
            Gate::forUser($fresh)->authorize($delivery ? 'prestamos.registrar_entrega' : 'prestamos.registrar_devolucion');
            Gate::forUser($fresh)->authorize('prestamos.ver_administrativos');
            $context = $this->operations->lock($target->equipamiento_id, true, $target->id);
            $restriction = null;
            try {
                $delivery ? $this->loans->assertDeliverable($context) : $this->loans->assertReceivable($fresh, $context, $data);
            } catch (EquipmentConflictException $error) {
                $restriction = $error->getMessage();
            }
            [$code, $reason, $now] = $this->check($payload, $type, $context['resource']->getKey(), $context['loan']->id);
            $reason ??= $restriction;
            $validation = $this->record($fresh, $payload, $code, $type, $context['resource']->getKey(), $context['loan']->id, $now, $reason);
            if ($validation->resultado === ResultadoValidacionQr::INVALIDA) {
                return $validation; // Commit rejection. Never throw it away with a domain exception.
            }
            $delivery ? $this->loans->deliverLocked($fresh, $context, $data) : $this->loans->receiveLocked($fresh, $context, $data);
            $milestone = $delivery ? $context['loan']->entrega()->firstOrFail() : $context['loan']->devolucion()->firstOrFail();
            $validation->forceFill([$delivery ? 'entrega_id' : 'devolucion_id' => $milestone->id])->save();

            return $validation;
        }, $sessionHash);
    }

    private function check(#[\SensitiveParameter] string $payload, TipoOperacionQr $type, int $resourceId, int $operationId): array
    {
        if (! $this->tokens->canonical($payload)) {
            return [null, 'Formato de código no admitido.', now()->toImmutable()];
        }
        $code = CodigoQr::where('token_hash', $this->tokens->hash($payload))->lockForUpdate()->first();
        $now = now()->toImmutable();
        $reason = match (true) {
            ! $code => 'Código no registrado.',
            ! $code->activo => 'Código inactivo.',
            $now->lt($code->generada_en), $code->vence_en && ! $now->lt($code->vence_en) => 'Código fuera de vigencia.',
            $code->recurso_id !== null => $code->recurso_id === $resourceId ? null : 'El código no corresponde al recurso esperado.',
            $type === TipoOperacionQr::USO_ESPACIO => $code->reserva_id === $operationId ? null : 'El código no corresponde a esta reserva.',
            default => $code->prestamo_id === $operationId ? null : 'El código no corresponde a este préstamo.',
        };

        return [$code, $reason, $now];
    }

    private function record(Usuario $actor, #[\SensitiveParameter] string $payload, ?CodigoQr $code, TipoOperacionQr $type, int $resourceId, int $operationId, $now, ?string $reason): ValidacionQr
    {
        $validation = new ValidacionQr;
        $validation->forceFill(['codigo_qr_id' => $code?->id, 'recurso_esperado_id' => $resourceId,
            'reserva_id' => $type === TipoOperacionQr::USO_ESPACIO ? $operationId : null,
            'prestamo_id' => $type === TipoOperacionQr::USO_ESPACIO ? null : $operationId,
            'entrega_id' => null, 'devolucion_id' => null, 'validado_por_id' => $actor->id, 'fecha_hora' => $now,
            'resultado' => $reason === null ? ResultadoValidacionQr::VALIDA : ResultadoValidacionQr::INVALIDA,
            'tipo_operacion' => $type, 'motivo_rechazo' => $reason, 'hash_leido' => $this->tokens->hash($payload)])->save();

        return $validation;
    }
}
