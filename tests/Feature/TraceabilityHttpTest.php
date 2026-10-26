<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\RegistroActividad;
use App\Models\Rol;
use App\Services\Traceability\ExternalReference;
use App\Services\Traceability\LocalEvidenceService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
        $this->assertSame('https://sepolia.etherscan.io/tx/0x'.str_repeat('b', 64), ExternalReference::explorer($valid));
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
