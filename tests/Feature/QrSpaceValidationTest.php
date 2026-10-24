<?php

namespace Tests\Feature;

use App\Enums\ResultadoValidacionQr;
use App\Models\EspacioAcademico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\QrTestCase;

class QrSpaceValidationTest extends QrTestCase
{
    public function test_resource_and_exact_reservation_codes_are_multiuse_evidence_without_state_changes(): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation($d);
        $before = $r->fresh()->getAttributes();
        $space = $r->espacio->fresh()->getAttributes();
        foreach ([['recurso_id' => $r->espacio_id], ['reserva_id' => $r->id]] as $target) {
            $token = $this->token();
            $code = $this->code($token, $target);
            for ($i = 0; $i < 2; $i++) {
                $v = $this->qr()->useSpace($d, $r, $token);
                $this->assertSame(ResultadoValidacionQr::VALIDA, $v->resultado);
                $this->assertNull($v->motivo_rechazo);
                $this->assertSame($r->id, $v->reserva_id);
                $this->assertSame($d->id, $v->validado_por_id);
                $this->assertSame($code->id, $v->codigo_qr_id);
                $this->assertNull($v->prestamo_id);
                $this->assertNull($v->entrega_id);
                $this->assertNull($v->devolucion_id);
                $this->assertSame(hash('sha256', $token), $v->hash_leido);
                $this->assertStringNotContainsString($token, $v->toJson());
                $this->assertArrayNotHasKey('hash_leido', $v->toArray());
            }
            $this->assertTrue($code->fresh()->activo);
        }
        $this->assertSame($before, $r->fresh()->getAttributes());
        $this->assertSame($space, $r->espacio->fresh()->getAttributes());
        $this->assertDatabaseCount('validaciones_qr', 4);
    }

    public static function states(): array
    {
        return array_map(fn ($s) => [$s], ['PENDIENTE', 'RECHAZADA', 'CANCELADA', 'LIBERADA', 'FINALIZADA']);
    }

    #[DataProvider('states')]
    public function test_other_reservation_states_are_invalid(string $state): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation($d, ['estado' => $state]);
        $token = $this->token();
        $this->code($token, ['recurso_id' => $r->espacio_id]);
        $this->assertSame(ResultadoValidacionQr::INVALIDA, $this->qr()->useSpace($d, $r, $token)->resultado);
        $this->assertSame($state, $r->fresh()->estado->value);
    }

    public function test_reservation_boundaries_are_exclusive_at_end_and_include_start(): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation($d);
        $token = $this->token();
        $this->code($token, ['reserva_id' => $r->id], ['generada_en' => $r->inicio->subDay()]);
        foreach ([[$r->inicio->subSecond(), false], [$r->inicio, true], [$r->fin->subSecond(), true], [$r->fin, false]] as [$when,$valid]) {
            $this->travelTo($when);
            $v = $this->qr()->useSpace($d, $r, $token);
            $this->assertSame($valid, $v->resultado === ResultadoValidacionQr::VALIDA);
        }
        $this->travelBack();
    }

    public function test_unknown_and_noncanonical_payloads_keep_exact_hash_without_dummy_code(): void
    {
        $d = $this->user('Estudiante');
        $r = $this->reservation($d);
        $token = $this->token();
        foreach ([$token, ' '.$token, $token.'=', 'inexistente', 'á'] as $payload) {
            $v = $this->qr()->useSpace($d, $r, $payload);
            $this->assertSame(ResultadoValidacionQr::INVALIDA, $v->resultado);
            $this->assertNull($v->codigo_qr_id);
            $this->assertSame(hash('sha256', $payload), $v->hash_leido);
            $this->assertNotEmpty($v->motivo_rechazo);
        }
        $this->assertDatabaseCount('codigos_qr', 0);
        $this->assertDatabaseCount('validaciones_qr', 5);
    }

    public function test_known_invalid_codes_preserve_fk_and_never_change_business(): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation($d);
        $before = $r->fresh()->getAttributes();
        foreach ([['activo' => false], ['vence_en' => now()->startOfSecond(), 'generada_en' => now()->subHour()], ['generada_en' => now()->addHour()]] as $extra) {
            $token = $this->token();
            $code = $this->code($token, ['reserva_id' => $r->id], $extra);
            $v = $this->qr()->useSpace($d, $r, $token);
            $this->assertSame(ResultadoValidacionQr::INVALIDA, $v->resultado);
            $this->assertSame($code->id, $v->codigo_qr_id);
        }
        $this->assertSame($before, $r->fresh()->getAttributes());
    }

    public function test_foreign_resource_and_same_resource_other_reservation_do_not_match(): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation($d);
        $other = $this->reservation($d, ['espacio_id' => $r->espacio_id, 'estado' => 'PENDIENTE']);
        foreach ([['recurso_id' => EspacioAcademico::factory()->create()->getKey()], ['reserva_id' => $other->id], ['prestamo_id' => $this->loan()->id]] as $target) {
            $token = $this->token();
            $code = $this->code($token, $target);
            $v = $this->qr()->useSpace($d, $r, $token);
            $this->assertSame(ResultadoValidacionQr::INVALIDA, $v->resultado);
            $this->assertSame($code->id, $v->codigo_qr_id);
        }
    }

    public function test_foreign_reservation_is_forbidden_before_attempt(): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation();
        try {
            $this->qr()->useSpace($d, $r, $this->token());
            $this->fail();
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('validaciones_qr', 0);
        }
    }

    public function test_administrative_use_requires_administrative_query_permission(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $r = $this->reservation();
        $token = $this->token();
        $this->code($token, ['reserva_id' => $r->id]);
        $this->assertSame(ResultadoValidacionQr::VALIDA, $this->qr()->useSpace($a, $r, $token)->resultado);
    }

    public static function restrictions(): array
    {
        return [['state'], ['block'], ['reservation'], ['incident']];
    }

    #[DataProvider('restrictions')]
    public function test_shared_availability_rejects_every_current_restriction_without_business_changes(string $restriction): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation($u);
        $t = $this->token();
        $this->code($t, ['reserva_id' => $r->id]);
        match ($restriction) {
            'state' => $r->espacio->forceFill(['estado' => 'EN_MANTENIMIENTO'])->save(),
            'block' => $this->blockFor($r->espacio, $r->inicio->format('Y-m-d H:i:s'), $r->fin->format('Y-m-d H:i:s')),
            'reservation' => $this->reservation($u, ['espacio_id' => $r->espacio_id]),
            'incident' => IncidenciaMantenimiento::factory()->create(['recurso_id' => $r->espacio_id])
        };
        $before = $r->fresh()->getRawOriginal();
        $space = $r->espacio->fresh()->getRawOriginal();
        $v = $this->qr()->useSpace($u, $r, $t);
        $this->assertSame('INVALIDA', $v->resultado->value);
        $this->assertSame($before, $r->fresh()->getRawOriginal());
        $this->assertSame($space, $r->espacio->fresh()->getRawOriginal());
    }

    public function test_query_permissions_are_required_for_both_administrators_and_owners(): void
    {
        foreach (['Administrador' => 'reservas.ver_administrativas', 'Docente' => 'reservas.ver_propias'] as $role => $permission) {
            $u = $this->user($role);
            $r = $this->reservation($u);
            Rol::where('nombre', $role)->sole()->permisos()->detach(Permiso::where('codigo', $permission)->sole());
            try {
                $this->qr()->useSpace($u, $r, $this->token());
                $this->fail();
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('validaciones_qr', 0);
            }
        }
    }
}
