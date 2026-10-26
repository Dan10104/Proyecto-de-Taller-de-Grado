<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\RegistroActividad;
use App\Models\Rol;
use App\Services\Traceability\BlockchainDriver;
use App\Services\Traceability\EvidenceProcessor;
use App\Services\Traceability\LocalEvidenceService;
use App\Services\Traceability\TraceabilityUnavailable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Tests\ReservationTestCase;
use Tests\Support\FakeBlockchainDriver;

class TraceabilityVerificationTest extends ReservationTestCase
{
    private FakeBlockchainDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new FakeBlockchainDriver;
        app()->instance(BlockchainDriver::class, $this->driver);
        config(['blockchain.enabled' => false, 'blockchain.contract_address' => '0x'.str_repeat('a', 40)]);
    }

    private function evidence()
    {
        $r = $this->pending();
        $this->service()->approve($this->user('Administrador'), $r);

        return app(LocalEvidenceService::class)->ensure(RegistroActividad::where('reserva_id', $r->id)->sole()->id);
    }

    public function test_disabled_local_creation_and_verification_never_attempt_rpc(): void
    {
        $e = $this->evidence();
        app(EvidenceProcessor::class)->register($e->id);
        $this->assertSame('PENDIENTE', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertNotNull($e->fresh()->ultima_verificacion);
        $this->assertSame(0, $this->driver->calls);
    }

    public function test_registration_retry_and_recovery_are_idempotent_and_rpc_outage_preserves_registered_state(): void
    {
        $e = $this->evidence();
        config(['blockchain.enabled' => true]);
        app(EvidenceProcessor::class)->register($e->id);
        $this->assertSame('REGISTRADA', $e->fresh()->estado->value);
        $this->assertSame('VALIDO', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertSame(1, $this->driver->broadcasts);
        app(EvidenceProcessor::class)->register($e->id);
        $this->assertSame(1, $this->driver->broadcasts);
        $original = $e->fresh()->only(['hash', 'version_canonica', 'referencia_externa', 'registrada_externamente_en']);
        $this->driver->unavailable = true;
        $this->assertSame('NO_VERIFICABLE', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertSame('REGISTRADA', $e->fresh()->estado->value);
        $this->assertEquals($original, $e->fresh()->only(array_keys($original)));
    }

    public function test_existing_chain_hash_recovers_without_broadcast(): void
    {
        $e = $this->evidence();
        $this->driver->register($e->hash);
        config(['blockchain.enabled' => true]);
        app(EvidenceProcessor::class)->register($e->id);
        $this->assertSame('REGISTRADA', $e->fresh()->estado->value);
        $this->assertSame(1, $this->driver->broadcasts);
    }

    public function test_local_mismatch_and_unknown_version_do_not_call_network(): void
    {
        $e = $this->evidence();
        config(['blockchain.enabled' => true]);
        DB::table('reservas')->where('id', $e->actividad->reserva_id)->update(['inicio' => '2091-01-10 08:00:00', 'fin' => '2091-01-10 09:00:00']);
        $this->assertSame('INCONSISTENTE', app(EvidenceProcessor::class)->verify($e->id));
        DB::table('evidencias_trazabilidad')->where('id', $e->id)->update(['version_canonica' => 'unknown']);
        $this->assertSame('NO_VERIFICABLE', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertSame(0, $this->driver->calls);
    }

    public function test_mainnet_is_blocked_and_pending_tx_is_never_sent_again(): void
    {
        $e = $this->evidence();
        config(['blockchain.enabled' => true]);
        $this->driver->chain = 1;
        try {
            app(EvidenceProcessor::class)->register($e->id);
            $this->fail();
        } catch (TraceabilityUnavailable) {
            $this->assertSame(0, $this->driver->broadcasts);
        }
        $this->driver->chain = 11155111;
        $e->forceFill(['referencia_externa' => 'eip155:11155111/'.config('blockchain.contract_address').'/0x'.str_repeat('b', 64)])->save();
        app(EvidenceProcessor::class)->register($e->id);
        $this->assertSame(0, $this->driver->broadcasts);
        $this->assertSame('PENDIENTE', app(EvidenceProcessor::class)->verify($e->id));
    }

    public function test_reauthorization_after_rpc_prevents_a_revoked_user_from_publishing_verification(): void
    {
        $e = $this->evidence();
        $admin = $this->user('Administrador');
        config(['blockchain.enabled' => true]);
        $this->driver->onFind = static function () {
            Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'trazabilidad.evidencias.verificar')->sole()->id);
        };
        try {
            app(EvidenceProcessor::class)->verify($e->id, $admin->id);
            $this->fail();
        } catch (AuthorizationException) {
            $this->assertNull($e->fresh()->ultima_verificacion);
        }
    }

    public function test_missing_required_history_is_not_verifiable_and_does_not_send_rpc(): void
    {
        $e = $this->evidence();
        config(['blockchain.enabled' => true]);
        DB::table('reservas')->where('id', $e->actividad->reserva_id)->update(['resuelta_en' => null]);
        $this->assertSame('NO_VERIFICABLE', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertSame(0, $this->driver->calls);
    }

    public function test_positive_external_contradictions_keep_registered_identity_for_review(): void
    {
        $e = $this->evidence();
        config(['blockchain.enabled' => true]);
        app(EvidenceProcessor::class)->register($e->id);
        $original = $e->fresh()->getAttributes();
        $this->driver->proofs[$e->hash] = ['status' => 'INCONSISTENT'];
        $this->assertSame('INCONSISTENTE', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertSame('REGISTRADA', $e->fresh()->estado->value);
        $this->assertSame($original['referencia_externa'], $e->fresh()->referencia_externa);
        $this->assertSame($original['hash'], $e->fresh()->hash);
        $this->driver->proofs[$e->hash] = ['status' => 'PENDING'];
        $this->assertSame('NO_VERIFICABLE', app(EvidenceProcessor::class)->verify($e->id));
    }

    public function test_confirmed_revert_remains_pending_for_verification_then_allows_same_hash_retry(): void
    {
        $e = $this->evidence();
        config(['blockchain.enabled' => true]);
        $e->forceFill(['referencia_externa' => 'eip155:11155111/'.config('blockchain.contract_address').'/0x'.str_repeat('b', 64)])->save();
        $this->driver->proofs[$e->hash] = ['status' => 'FAILED', 'reference' => $e->referencia_externa];
        $this->assertSame('PENDIENTE', app(EvidenceProcessor::class)->verify($e->id));
        $this->assertNull($e->fresh()->registrada_externamente_en);
        $this->assertSame(0, $this->driver->broadcasts);
        app(EvidenceProcessor::class)->register($e->id);
        $this->assertSame('REGISTRADA', $e->fresh()->estado->value);
        $this->assertNotSame($e->referencia_externa, $e->fresh()->referencia_externa);
        $this->assertSame(1, $this->driver->broadcasts);
    }
}
