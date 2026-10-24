<?php

namespace Tests\Feature;

use App\Models\CodigoQr;
use App\Models\EspacioAcademico;
use App\Services\QrContextService;
use App\Services\QrManagementService;
use App\Services\QrTokenService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\QrTestCase;

class QrLifecycleTest extends QrTestCase
{
    public function test_tokens_are_canonical_random_256_bit_and_hash_exact_bytes(): void
    {
        $tokens = app(QrTokenService::class);
        $seen = [];
        for ($i = 0; $i < 100; $i++) {
            $token = $tokens->generate();
            $seen[] = $token;
            $this->assertSame(43, strlen($token));
            $this->assertTrue($tokens->canonical($token));
            $this->assertSame(32, strlen(base64_decode(strtr($token, '-_', '+/').'=', true)));
            $this->assertSame(hash('sha256', $token), $tokens->hash($token));
            foreach ([' '.$token, $token.' ', $token.'=', 'https://example.test/'.$token] as $invalid) {
                $this->assertFalse($tokens->canonical($invalid));
            }
        }
        $this->assertCount(100, array_unique($seen));
    }

    public function test_management_rotates_and_preserves_history_without_persisting_token_or_svg(): void
    {
        $a = $this->user('Administrador');
        $r = EspacioAcademico::factory()->create();
        $service = app(QrManagementService::class);
        $first = $service->issue($a, 'recurso', $r->getKey());
        $this->assertStringContainsString('<svg', $first['svg']);
        $this->assertArrayNotHasKey('token', $first);
        $second = $service->issue($a, 'recurso', $r->getKey());
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertFalse(CodigoQr::find($first['id'])->activo);
        $this->assertSame(1, CodigoQr::where('activo', true)->count());
        $this->assertNotSame(CodigoQr::find($first['id'])->token_hash, CodigoQr::find($second['id'])->token_hash);
        $this->assertNull(CodigoQr::find($second['id'])->vence_en);
        $this->assertArrayNotHasKey('token_hash', CodigoQr::find($second['id'])->toArray());
        $service->deactivate($a, 'recurso', $r->getKey(), $second['id']);
        $service->deactivate($a, 'recurso', $r->getKey(), $second['id']);
        $this->assertDatabaseCount('codigos_qr', 2);
        $this->assertSame(0, CodigoQr::where('activo', true)->count());
    }

    public function test_operations_and_resource_are_independent_optional_targets(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $r = $this->reservation();
        $p = $this->loan();
        $this->assertDatabaseCount('codigos_qr', 0);
        $s = app(QrManagementService::class);
        foreach ([['recurso', $r->espacio_id], ['reserva', $r->id], ['prestamo', $p->id], ['recurso', $p->equipamiento_id]] as [$type,$id]) {
            $s->issue($a, $type, $id);
        }
        $this->assertSame(4, CodigoQr::where('activo', true)->count());
    }

    public function test_requesters_cannot_issue_even_for_own_reservation(): void
    {
        $d = $this->user('Docente');
        $r = $this->reservation($d);
        $this->expectException(AuthorizationException::class);
        app(QrManagementService::class)->issue($d, 'reserva', $r->id);
    }

    public function test_past_expiry_is_rejected_and_keeps_previous_active_code(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $s = app(QrManagementService::class);
        $s->issue($a, 'reserva', $r->id);
        try {
            $s->issue($a, 'reserva', $r->id, now()->subMinute()->format('Y-m-d\TH:i'));
            $this->fail();
        } catch (ValidationException) {
            $this->assertSame(1, CodigoQr::where('activo', true)->count());
        }
    }

    public function test_hash_collision_exhaustion_rolls_back_deactivation(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $token = $this->token();
        $code = $this->code($token, ['reserva_id' => $r->id]);
        $mock = \Mockery::mock(QrTokenService::class)->makePartial();
        $mock->shouldReceive('generate')->times(3)->andReturn($token);
        $this->app->instance(QrTokenService::class, $mock);
        try {
            app(QrManagementService::class)->issue($a, 'reserva', $r->id);
            $this->fail();
        } catch (ValidationException) {
            $this->assertTrue($code->fresh()->activo);
            $this->assertDatabaseCount('codigos_qr', 1);
        }
    }

    public function test_renderer_failure_restores_previous_active_code(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $code = $this->code($this->token(), ['reserva_id' => $r->id]);
        $service = \Mockery::mock(QrManagementService::class, [app(QrTokenService::class), app(QrContextService::class)])->makePartial();
        $service->shouldReceive('render')->andThrow(new \RuntimeException('Fallo sintético de renderizado'));
        try {
            $service->issue($a, 'reserva', $r->id);
            $this->fail();
        } catch (\RuntimeException) {
            $this->assertTrue($code->fresh()->activo);
            $this->assertDatabaseCount('codigos_qr', 1);
        }
    }

    public function test_noncanonical_last_bits_case_uri_unicode_and_escapes_never_alias_known_token(): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation($u);
        $t = $this->token();
        $this->code($t, ['reserva_id' => $r->id]);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $badBits = substr($t, 0, 42).$alphabet[strpos($alphabet, $t[42]) + 1];
        foreach ([$badBits, strtr($t, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 'https://example.test/'.$t, '\\u'.bin2hex($t), 'á'.$t] as $payload) {
            $v = $this->qr()->useSpace($u, $r, $payload);
            $this->assertSame('INVALIDA', $v->resultado->value);
            $this->assertNull($v->codigo_qr_id);
            $this->assertTrue(hash_equals(hash('sha256', $payload), $v->hash_leido));
        }
    }
}
