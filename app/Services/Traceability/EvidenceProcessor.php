<?php

namespace App\Services\Traceability;

use App\Models\EvidenciaTrazabilidad;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class EvidenceProcessor
{
    public function __construct(private LocalEvidenceService $local, private BlockchainDriver $driver, private TraceabilityLock $locks) {}

    public function register(int $id): void
    {
        $this->locks->run('evidence:'.$id, function () use ($id) {
            $e = EvidenciaTrazabilidad::findOrFail($id);
            if ($e->estado->value === 'REGISTRADA' || $this->local->check($e) !== 'VALIDO' || ! config('blockchain.enabled')) {
                return;
            }
            $this->assertChain();
            $proof = $this->driver->find($e->hash, $e->referencia_externa);
            $failed = ($proof['status'] ?? null) === 'FAILED';
            if ($failed) {
                Log::warning('traceability.reverted', ['evidence_id' => $e->id]);
            }
            if ((($proof['status'] ?? null) === 'ABSENT' && $e->referencia_externa === null) || $failed) {
                // Serialize the signer across all workers; no business transaction/row lock spans RPC.
                $proof = $this->locks->run('wallet:11155111:'.hash('sha256', strtolower(config('blockchain.private_key', ''))), function () use ($e, $failed) {
                    $this->assertChain();
                    if ($failed && ($this->driver->find($e->hash, $e->referencia_externa)['status'] ?? null) !== 'FAILED') {
                        return ['status' => 'PENDING'];
                    }
                    if ($this->driver->isRegistered($e->hash)) {
                        return $this->driver->find($e->hash);
                    }

                    return $this->driver->register($e->hash);
                });
            }
            if ($failed && in_array($proof['status'] ?? '', ['PENDING', 'CONFIRMED'], true) && ExternalReference::parse($proof['reference'] ?? null)) {
                // Only a positively confirmed revert permits replacing a pending candidate.
                $e->forceFill(['referencia_externa' => null]);
            }
            $this->applyProof($e, $proof);
        });
    }

    public function verify(int $id, ?int $actorId = null): string
    {
        return $this->locks->run('evidence:'.$id, function () use ($id, $actorId) {
            $e = EvidenciaTrazabilidad::findOrFail($id);
            $result = $this->local->check($e);
            $proof = null;
            if ($result === 'VALIDO') {
                if (($e->estado->value === 'REGISTRADA' && $e->referencia_externa === null) || ($e->referencia_externa !== null && ! ExternalReference::parse($e->referencia_externa))) {
                    $result = 'INCONSISTENTE';
                } elseif (! config('blockchain.enabled')) {
                    $result = $e->estado->value === 'REGISTRADA' ? 'NO_VERIFICABLE' : 'PENDIENTE';
                } else {
                    try {
                        $this->assertChain();
                        $proof = $this->driver->find($e->hash, $e->referencia_externa);
                        $result = match ($proof['status'] ?? '') {
                            'CONFIRMED' => $this->proofMatches($e, $proof) ? 'VALIDO' : 'INCONSISTENTE',
                            'INCONSISTENT' => 'INCONSISTENTE',
                            'FAILED' => $e->estado->value === 'REGISTRADA' ? 'INCONSISTENTE' : 'PENDIENTE',
                            'ABSENT' => $e->estado->value === 'REGISTRADA' ? 'INCONSISTENTE' : 'PENDIENTE',
                            default => $e->estado->value === 'REGISTRADA' ? 'NO_VERIFICABLE' : 'PENDIENTE',
                        };
                    } catch (TraceabilityUnavailable) {
                        $result = $e->estado->value === 'REGISTRADA' ? 'NO_VERIFICABLE' : 'PENDIENTE';
                    }
                }
            }
            $e->refresh();
            $localNow = $this->local->check($e);
            if ($localNow !== 'VALIDO') {
                $result = $localNow;
            }
            DB::transaction(function () use ($e, $actorId, $result, $proof) {
                // Permissions are checked again after network wait, under the shared security lock.
                if ($actorId !== null) {
                    Rol::where('nombre', 'Administrador')->lockForUpdate()->firstOrFail();
                    Gate::forUser(Usuario::findOrFail($actorId))->authorize('trazabilidad.evidencias.ver');
                    Gate::forUser(Usuario::findOrFail($actorId))->authorize('trazabilidad.evidencias.verificar');
                }
                if ($result === 'VALIDO' && isset($proof)) {
                    $this->applyProof($e, $proof);
                }
                $e->forceFill(['ultima_verificacion' => now(), 'resultado_verificacion' => $result])->save();
            });

            return $result;
        });
    }

    private function assertChain(): void
    {
        if (config('blockchain.chain_id') !== 11155111 || config('blockchain.network') !== 'sepolia' || $this->driver->chainId() !== 11155111) {
            throw new TraceabilityUnavailable('WRONG_CHAIN');
        }
    }

    private function applyProof(EvidenciaTrazabilidad $e, array $proof): void
    {
        if ($e->estado->value === 'REGISTRADA') {
            return;
        }
        $ref = $proof['reference'] ?? null;
        $parsed = ExternalReference::parse($ref);
        if (! $parsed || $parsed['contract'] !== strtolower(config('blockchain.contract_address', '')) || ($e->referencia_externa !== null && $e->referencia_externa !== $ref)) {
            return;
        }
        if (($proof['status'] ?? '') === 'CONFIRMED' && $this->proofMatches($e, $proof)) {
            $this->publish($e, ['referencia_externa' => $ref, 'registrada_externamente_en' => CarbonImmutable::createFromTimestampUTC($proof['timestamp'])->setTimezone('America/La_Paz'), 'estado' => 'REGISTRADA']);
        } elseif (($proof['status'] ?? '') === 'PENDING' && $e->referencia_externa === null) {
            $this->publish($e, ['referencia_externa' => $ref]);
        }
    }

    private function publish(EvidenciaTrazabilidad $e, array $changes): void
    {
        DB::transaction(function () use ($e, $changes) {
            $current = EvidenciaTrazabilidad::whereKey($e->id)->lockForUpdate()->firstOrFail();
            foreach (['registro_actividad_id', 'algoritmo_hash', 'hash', 'version_canonica', 'referencia_externa', 'estado'] as $field) {
                if ($current->getRawOriginal($field) !== $e->getRawOriginal($field)) {
                    throw new TraceabilityUnavailable('EVIDENCE_CHANGED');
                }
            }
            $current->forceFill($changes)->save();
            $e->setRawAttributes($current->getAttributes(), true);
        });
    }

    private function proofMatches(EvidenciaTrazabilidad $e, array $proof): bool
    {
        $parsed = ExternalReference::parse($proof['reference'] ?? null);
        if (! $parsed || $parsed['contract'] !== strtolower(config('blockchain.contract_address', '')) || ($e->referencia_externa !== null && $e->referencia_externa !== $proof['reference'])
            || ! isset($proof['timestamp']) || ! is_int($proof['timestamp']) || $proof['timestamp'] <= 0) {
            return false;
        }

        return $e->estado->value !== 'REGISTRADA' || ($e->registrada_externamente_en !== null && $e->registrada_externamente_en->timestamp === $proof['timestamp']);
    }
}
