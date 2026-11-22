<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\RegistroActividad;
use App\Models\Rol;
use App\Services\Traceability\EthereumSepoliaDriver;
use App\Services\Traceability\ExternalReference;
use App\Services\Traceability\LocalEvidenceService;
use App\Services\Traceability\TraceabilityUnavailable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\ReservationTestCase;
use Tests\Support\EnforceCsrf;

#[Group('traceability')]
class TraceabilityHttpTest extends ReservationTestCase
{
    private function evidence()
    {
        $this->service()->approve($this->user('Administrador'), $this->pending());

        return app(LocalEvidenceService::class)->ensure(RegistroActividad::sole()->id);
    }

    public static function roles(): array
    {
        return [['Docente'], ['Estudiante'], ['Encargado de Laboratorio']];
    }

    #[DataProvider('roles')]
    public function test_other_roles_cannot_view_verify_or_retry_even_with_accidental_grants(string $role): void
    {
        $e = $this->evidence();
        $user = $this->user($role);
        Rol::where('nombre', $role)->sole()->permisos()->syncWithoutDetaching(Permiso::where('codigo', 'like', 'trazabilidad.%')->pluck('id'));
        $this->login($user);
        $this->get('/trazabilidad')->assertForbidden();
        $this->get('/trazabilidad/'.$e->id)->assertForbidden();
        $this->post('/trazabilidad/'.$e->id.'/verificar')->assertForbidden();
        $this->post('/trazabilidad/'.$e->id.'/reintentar')->assertForbidden();
        $this->assertNull($e->fresh()->ultima_verificacion);
    }

    public function test_admin_list_filters_detail_verify_retry_and_permission_revocation(): void
    {
        $e = $this->evidence();
        $this->login($this->user('Administrador'));
        $this->get('/trazabilidad?estado=PENDIENTE&operacion=RESERVA_APROBADA')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Traceability/Index')->has('evidencias.data', 1));
        $this->get('/trazabilidad/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->component('Traceability/Show')->where('evidencia.hash', $e->hash)->missing('evidencia.actividad.descripcion')->missing('evidencia.actividad.actor')->missing('blockchain')->missing('canonical')->where('explorador', null));
        $this->post('/trazabilidad/'.$e->id.'/verificar')->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/trazabilidad/'.$e->id.'/reintentar')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($e->fresh()->ultima_verificacion);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'trazabilidad.evidencias.verificar')->sole()->id);
        $this->post('/trazabilidad/'.$e->id.'/verificar')->assertForbidden();
    }

    public function test_registered_evidence_cannot_be_retried_and_external_reference_is_strict(): void
    {
        $e = $this->evidence();
        $e->forceFill(['estado' => 'REGISTRADA'])->save();
        $this->login($this->user('Administrador'));
        $this->post('/trazabilidad/'.$e->id.'/reintentar')->assertStatus(409);
        foreach ([null, 'https://evil.test', 'eip155:1/0x'.str_repeat('a', 40).'/0x'.str_repeat('b', 64), 'eip155:11155111/0x'.str_repeat('A', 40).'/0x'.str_repeat('b', 64)] as $bad) {
            $this->assertNull(ExternalReference::explorer($bad));
        }
        $valid = 'eip155:11155111/0x'.str_repeat('a', 40).'/0x'.str_repeat('b', 64);
        // A well-formed reference is linked to the public explorer only when it belongs to the configured contract.
        $this->assertNull(ExternalReference::explorer($valid));
        config(['blockchain.contract_address' => '0x'.str_repeat('a', 40)]);
        $this->assertSame('https://sepolia.etherscan.io/tx/0x'.str_repeat('b', 64), ExternalReference::explorer($valid));
    }

    public function test_local_simulation_is_labelled_and_never_linked_to_the_public_explorer(): void
    {
        $e = $this->evidence();
        $contract = '0x'.str_repeat('a', 40);
        $transaction = '0x'.str_repeat('b', 64);
        $reference = 'eip155:11155111/'.$contract.'/'.$transaction;
        $public = '0x'.str_repeat('d', 40);
        // The local deployment records its contract; from then on its references are local records.
        config(['blockchain.environment' => 'local-simulation', 'blockchain.contract_address' => $contract, 'blockchain.simulation_contracts' => [$contract]]);
        $this->assertNull(ExternalReference::explorer($reference));
        $this->assertSame(['simulada' => true, 'otro_despliegue' => false, 'contrato' => $contract, 'transaccion' => $transaction], ExternalReference::network($reference));
        $this->assertSame(['simulada' => true, 'otro_despliegue' => false, 'contrato' => null, 'transaccion' => null], ExternalReference::network(null));
        // A reference to an unknown contract is another deployment: neither the current network nor a public link.
        $foreign = 'eip155:11155111/0x'.str_repeat('c', 40).'/'.$transaction;
        $this->assertSame(['simulada' => false, 'otro_despliegue' => true], array_slice(ExternalReference::network($foreign), 0, 2));
        $this->assertNull(ExternalReference::explorer($foreign));

        $this->login($this->user('Administrador'));
        // Block and confirmations last seen on the network are shown only once the evidence is registered.
        Cache::put(EthereumSepoliaDriver::proofKey($reference), ['block' => 7, 'confirmations' => 3, 'seen_at' => now()->toIso8601String()], 60);
        $this->get('/trazabilidad/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('red.simulada', true)->where('red.otro_despliegue', false)->where('explorador', null)->where('confirmacion', null));
        $e->forceFill(['estado' => 'REGISTRADA', 'referencia_externa' => $reference, 'registrada_externamente_en' => now()])->save();
        $this->get('/trazabilidad/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('red.simulada', true)->where('red.transaccion', $transaction)->where('red.contrato', $contract)
            ->where('explorador', null)->where('confirmacion.bloque', 7)->where('confirmacion.confirmaciones', 3));

        // Switching the configuration to the public testnet does not move a local record there: it stays labelled
        // as a local simulation, without public link, and the block seen on the local network is not shown.
        config(['blockchain.environment' => 'public', 'blockchain.contract_address' => $public]);
        $this->get('/trazabilidad/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('red.simulada', true)->where('red.otro_despliegue', true)->where('explorador', null)->where('confirmacion', null));
        // Not even when the public configuration keeps pointing at the local contract address.
        config(['blockchain.contract_address' => $contract]);
        $this->get('/trazabilidad/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('red.simulada', true)->where('explorador', null)->where('confirmacion', null));
        // And without the record of local deployments, a reference to another contract still gets no public link.
        config(['blockchain.contract_address' => $public, 'blockchain.simulation_contracts' => []]);
        $this->get('/trazabilidad/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('red.simulada', false)->where('red.otro_despliegue', true)->where('explorador', null));

        // The other direction: a public record keeps its public link only while its own contract is configured,
        // and is never labelled as a simulation after switching to the local environment.
        $publicReference = 'eip155:11155111/'.$public.'/'.$transaction;
        $this->assertSame('https://sepolia.etherscan.io/tx/'.$transaction, ExternalReference::explorer($publicReference));
        config(['blockchain.environment' => 'local-simulation', 'blockchain.contract_address' => $contract, 'blockchain.simulation_contracts' => [$contract]]);
        $this->assertNull(ExternalReference::explorer($publicReference));
        $this->assertSame(['simulada' => false, 'otro_despliegue' => true], array_slice(ExternalReference::network($publicReference), 0, 2));
    }

    public function test_a_display_cache_failure_only_omits_the_block_summary(): void
    {
        $reference = 'eip155:11155111/0x'.str_repeat('a', 40).'/0x'.str_repeat('b', 64);
        Cache::shouldReceive('get')->andThrow(new \RuntimeException('cache down'));
        $this->assertNull(EthereumSepoliaDriver::lastSeen($reference));
        $this->assertNull(EthereumSepoliaDriver::lastSeen(null));
    }

    public function test_driver_refuses_an_unknown_environment_and_the_simulation_in_production(): void
    {
        config(['blockchain.enabled' => true, 'blockchain.chain_id' => 11155111, 'blockchain.network' => 'sepolia', 'blockchain.environment' => 'testnet', 'blockchain.node' => 'node-binary-that-must-not-run']);
        $reason = function (): string {
            try {
                (new EthereumSepoliaDriver)->chainId();
            } catch (TraceabilityUnavailable $unavailable) {
                return $unavailable->reason;
            }

            return 'RAN';
        };
        // Refused from configuration alone, before any external process is started.
        $this->assertSame('WRONG_ENVIRONMENT', $reason());
        config(['blockchain.environment' => 'local-simulation']);
        $this->app['env'] = 'production';
        $this->assertSame('WRONG_ENVIRONMENT', $reason());
        $this->assertTrue(EthereumSepoliaDriver::simulated());
        config(['blockchain.environment' => 'public']);
        $this->assertFalse(EthereumSepoliaDriver::simulated());
    }

    public function test_actions_also_require_view_and_csrf(): void
    {
        $e = $this->evidence();
        $this->login($this->user('Administrador'));
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'trazabilidad.evidencias.ver')->sole()->id);
        $this->post('/trazabilidad/'.$e->id.'/verificar')->assertForbidden();
        $this->post('/trazabilidad/'.$e->id.'/reintentar')->assertForbidden();
        Rol::where('nombre', 'Administrador')->sole()->permisos()->attach(Permiso::where('codigo', 'trazabilidad.evidencias.ver')->sole()->id);
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        $this->post('/trazabilidad/'.$e->id.'/verificar')->assertStatus(419);
        $this->post('/trazabilidad/'.$e->id.'/reintentar')->assertStatus(419);
    }
}
