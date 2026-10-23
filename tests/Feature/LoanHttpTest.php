<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\Permiso;
use App\Models\Prestamo;
use App\Models\Rol;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\LoanTestCase;
use Tests\Support\EnforceCsrf;

class LoanHttpTest extends LoanTestCase
{
    public function test_full_http_happy_path_and_private_dtos(): void
    {
        $u = $this->user('Docente');
        $e = EquipamientoTecnologico::factory()->create();
        $data = $this->loanData($e);
        $this->login($u);
        $this->get('/prestamos/crear?'.http_build_query(array_diff_key($data, ['motivo' => true])))->assertOk();
        $this->post('/prestamos', $data)->assertRedirect();
        $p = Prestamo::sole();
        $this->get('/prestamos/'.$p->id)->assertOk()->assertInertia(fn (Assert $v) => $v->component('Loans/Show')->where('prestamo.estado', 'PENDIENTE')->missing('prestamo.solicitante')->missing('prestamo.solicitante_id'));
        $this->post('/logout');
        $a = $this->user('Encargado de Laboratorio');
        $this->login($a);
        $this->post('/prestamos/'.$p->id.'/aprobar')->assertRedirect();
        $this->post('/prestamos/'.$p->id.'/entregar', $this->deliveryData())->assertRedirect();
        $this->get('/prestamos/'.$p->id)->assertOk()->assertInertia(fn (Assert $v) => $v->where('prestamo.estado', 'ENTREGADO')->where('prestamo.entrega.recepcion_confirmada_en', null)->missing('prestamo.solicitante.correo')->missing('prestamo.solicitante.password_hash'));
        $this->post('/prestamos/'.$p->id.'/devolver', ['condicion' => 'ADECUADA'])->assertRedirect();
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
    }

    public function test_own_scope_precedes_filters_options_counts_and_pagination(): void
    {
        $u = $this->user('Estudiante');
        $own = $this->loan($u);
        $foreign = $this->loan();
        $this->login($u);
        $this->get('/prestamos')->assertOk()->assertInertia(fn (Assert $v) => $v->where('prestamos.total', 1)->where('prestamos.data.0.id', $own->id)->has('opciones.equipamientos', 1)->has('opciones.solicitantes', 0));
        $this->get('/prestamos?equipamiento_id='.$foreign->equipamiento_id)->assertOk()->assertInertia(fn ($v) => $v->where('prestamos.total', 0)->has('opciones.equipamientos', 1));
        $this->get('/prestamos/'.$foreign->id)->assertForbidden();
        $this->get('/prestamos?alcance=administrativos')->assertForbidden();
        $this->get('/prestamos?solicitante_id='.$foreign->solicitante_id)->assertSessionHasErrors('solicitante_id');
        $this->post('/prestamos/'.$own->id.'/aprobar')->assertForbidden();
        $this->post('/prestamos/'.$own->id.'/entregar', $this->deliveryData())->assertForbidden();
        $this->post('/prestamos/'.$own->id.'/devolver', ['condicion' => 'ADECUADA'])->assertForbidden();
    }

    public function test_request_rejects_forged_server_fields_and_invalid_periods(): void
    {
        $this->login($this->user('Docente'));
        $e = EquipamientoTecnologico::factory()->create();
        $data = $this->loanData($e);
        foreach (['solicitante_id' => 1, 'codigo' => 'PRE-FAKE', 'estado' => 'APROBADO', 'resuelto_por_id' => 1, 'resuelto_en' => '2000-01-01', 'solicitado_en' => '2000-01-01'] as $field => $value) {
            $this->post('/prestamos', [...$data, $field => $value])->assertSessionHasErrors($field);
        }
        foreach ([['motivo' => '  '], ['motivo' => str_repeat('x', 501)], ['fin_previsto' => $data['inicio_previsto']], ['fin_previsto' => '2090-01-10T07:00'], ['equipamiento_id' => 999999]] as $bad) {
            $this->post('/prestamos', [...$data, ...$bad])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_delivery_return_and_resolution_payloads_cannot_forge_hitos(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->login($a);
        $base = '/prestamos/'.$p->id;
        $this->post($base.'/rechazar', ['motivo_rechazo' => ' '])->assertSessionHasErrors('motivo_rechazo');
        $this->post($base.'/aprobar', ['resuelto_por_id' => 999])->assertSessionHasErrors('resuelto_por_id');
        $this->post($base.'/aprobar')->assertRedirect();
        foreach (['receptor_id' => 999, 'entregado_por_id' => 999, 'fecha_hora' => '2000-01-01', 'recepcion_confirmada_en' => '2000-01-01'] as $field => $value) {
            $this->post($base.'/entregar', [...$this->deliveryData(), $field => $value])->assertSessionHasErrors($field);
        }
        $this->post($base.'/entregar', $this->deliveryData(['equipo_verificado' => false]))->assertSessionHasErrors('equipo_verificado');
        $this->post($base.'/entregar', $this->deliveryData(['receptor_verificado' => false]))->assertSessionHasErrors('receptor_verificado');
        $this->post($base.'/entregar', $this->deliveryData(['condicion_entrega' => str_repeat('x', 256)]))->assertSessionHasErrors('condicion_entrega');
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->post($base.'/entregar', $this->deliveryData())->assertRedirect();
        foreach (['recibido_por_id' => 999, 'confirmado_por_id' => 999, 'registrada_en' => '2000-01-01', 'confirmada_en' => '2000-01-01', 'estado' => 'CONFIRMADA', 'devolucion_id' => 1] as $field => $value) {
            $this->post($base.'/devolver', ['condicion' => 'ADECUADA', $field => $value])->assertSessionHasErrors($field);
        }
        foreach (['CON_INCIDENCIA', 'NO_DETERMINABLE'] as $condition) {
            $this->post($base.'/devolver', ['condicion' => $condition])->assertSessionHasErrors('descripcion_condicion');
        }
        $this->post($base.'/devolver', ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisión'])->assertRedirect();
        $this->post($base.'/revisar', ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisión'])->assertSessionHasErrors('condicion');
    }

    public function test_admin_is_not_requester_and_permission_revocation_blocks_previously_open_form(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->login($a);
        $this->post('/prestamos', $this->loanData($p->equipamiento))->assertForbidden();
        $this->get('/prestamos/'.$p->id)->assertOk();
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'prestamos.resolver')->sole());
        $this->post('/prestamos/'.$p->id.'/aprobar')->assertForbidden();
        $this->assertSame('PENDIENTE', $p->fresh()->estado->value);
    }

    public function test_conflicts_are_sanitized_json_409_and_double_post_does_not_overwrite(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->login($a);
        $base = '/prestamos/'.$p->id;
        $this->post($base.'/aprobar')->assertRedirect();
        $before = $p->fresh()->getRawOriginal();
        $response = $this->withCredentials()->postJson($base.'/aprobar');
        $response->assertStatus(409)->assertJsonStructure(['message']);
        $this->assertStringNotContainsString('SQL', $response->getContent());
        $this->assertSame($before, $p->fresh()->getRawOriginal());
    }

    public function test_query_filters_vencido_review_date_and_administrative_requester(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->travelTo($p->fin_previsto->addMinute());
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisar']);
        $this->loan();
        $this->login($a);
        $this->get('/prestamos?alcance=administrativos&vencidos=1&revision=1&fecha=2090-01-10&solicitante_id='.$p->solicitante_id)->assertOk()->assertInertia(fn ($v) => $v->where('prestamos.total', 1)->where('prestamos.data.0.vencido', true)->where('prestamos.data.0.revision_pendiente', true));
        $this->travelBack();
    }

    public function test_csrf_and_invalid_session_reject_operational_writes(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->login($a);
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        foreach (['aprobar', 'rechazar', 'entregar', 'devolver', 'revisar'] as $action) {
            $this->post('/prestamos/'.$p->id.'/'.$action)->assertStatus(419);
        }
        $this->post('/incidencias', $this->incidentData($p->equipamiento_id))->assertStatus(419);
        $this->post('/equipamientos', $this->equipmentData())->assertStatus(419);
        $this->assertSame('PENDIENTE', $p->fresh()->estado->value);
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->post('/logout',['_token' => session()->token()])->assertRedirect('/login');
        $this->get('/prestamos/'.$p->id)->assertRedirect('/login');
    }
}
