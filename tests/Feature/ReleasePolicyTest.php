<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\PoliticaLiberacion;
use App\Models\Reserva;
use App\Services\ReleasePolicyManagementService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseTestCase;

class ReleasePolicyTest extends ReleaseTestCase
{
    private function data(array $extra = []): array
    {
        return ['nombre' => 'Regla sintética', 'version' => 1, 'tolerancia_min' => 0, 'requiere_qr' => true, 'usa_iot' => false, 'max_antiguedad_lectura_min' => null, 'modo' => 'ALERTA', 'activa' => true, ...$extra];
    }

    public static function invalid(): array
    {
        return [[['nombre' => ' ']], [['nombre' => str_repeat('a', 121)]], [['version' => 0]], [['version' => 1.5]], [['version' => 2147483648]], [['tolerancia_min' => -1]], [['tolerancia_min' => 2147483648]], [['requiere_qr' => false]], [['requiere_qr' => 'true']], [['usa_iot' => 'yes']], [['activa' => 'false']], [['modo' => 'LIBERAR']], [['usa_iot' => true]], [['usa_iot' => true, 'max_antiguedad_lectura_min' => -1]], [['usa_iot' => true, 'max_antiguedad_lectura_min' => 2147483648]], [['max_antiguedad_lectura_min' => 0]]];
    }

    #[DataProvider('invalid')]
    public function test_invalid_configuration_is_rejected(array $extra): void
    {
        $actor = $this->user('Administrador');
        $this->expectException(ValidationException::class);
        app(ReleasePolicyManagementService::class)->create($actor, $this->data($extra));
    }

    public function test_versions_are_valid_trimmed_unique_immutable_and_never_deleted(): void
    {
        $actor = $this->user('Encargado de Laboratorio');
        $service = app(ReleasePolicyManagementService::class);
        $a = $service->create($actor, $this->data(['nombre' => ' Regla sintética ']));
        $b = $service->create($actor, $this->data(['version' => 2, 'requiere_qr' => false, 'usa_iot' => true, 'max_antiguedad_lectura_min' => 0]));
        $c = $service->create($actor, $this->data(['version' => 2147483647, 'usa_iot' => true, 'tolerancia_min' => 2147483647, 'max_antiguedad_lectura_min' => 2147483647]));
        $this->assertSame('Regla sintética', $a->nombre);
        $this->assertNotSame($a->id, $b->id);
        $this->assertFalse($a->fresh()->usa_iot);
        $this->assertSame(0, $b->max_antiguedad_lectura_min);
        foreach (['nombre' => 'Cambio', 'version' => 5, 'tolerancia_min' => 1, 'requiere_qr' => false, 'usa_iot' => true, 'max_antiguedad_lectura_min' => 2, 'modo' => 'AUTOMATICA'] as $field => $value) {
            try {
                $a->fresh()->forceFill([$field => $value])->save();
                $this->fail('La versión debe ser inmutable.');
            } catch (\LogicException) {
                $this->assertTrue(true);
            }
        }
        try {
            $a->delete();
            $this->fail('No se permite borrar.');
        } catch (\LogicException) {
            $this->assertSame(3, PoliticaLiberacion::count());
        }
        $this->expectException(ValidationException::class);
        $service->create($actor, $this->data(['nombre' => 'REGLA SINTÉTICA']));
    }

    public function test_assignment_changes_including_null_are_guarded_but_same_id_is_noop(): void
    {
        $actor = $this->user('Administrador');
        $service = app(ReleasePolicyManagementService::class);
        $p = PoliticaLiberacion::factory()->create();
        $q = PoliticaLiberacion::factory()->create();
        foreach ([[null, $p->id], [$p->id, null], [$p->id, $q->id]] as [$before, $after]) {
            foreach (['08:00:00', '08:30:00'] as $start) {
                $r = Reserva::factory()->aprobada()->create(['inicio' => '2090-01-10 '.$start]);
                $r->espacio->forceFill(['politica_liberacion_id' => $before])->save();
                try {
                    $service->assign($actor, $r->espacio_id, $after);
                    $this->fail('Debe proteger compromisos activos y futuros.');
                } catch (ValidationException) {
                    $this->assertSame($before, $r->espacio->fresh()->politica_liberacion_id);
                }
                $service->assign($actor, $r->espacio_id, $before);
                $this->assertSame($before, $r->espacio->fresh()->politica_liberacion_id);
            }
        }
        $space = EspacioAcademico::factory()->create();
        $service->assign($actor, $space->recurso_id, $p->id);
        $this->assertSame($p->id, $space->fresh()->politica_liberacion_id);
        $service->assign($actor, $space->recurso_id, null);
        $this->assertNull($space->fresh()->politica_liberacion_id);
    }

    public function test_expired_and_unapproved_reservations_do_not_prevent_assignment(): void
    {
        $actor = $this->user('Administrador');
        $policy = PoliticaLiberacion::factory()->create();
        foreach (['PENDIENTE', 'RECHAZADA', 'CANCELADA', 'LIBERADA', 'FINALIZADA', 'APROBADA'] as $state) {
            $r = Reserva::factory()->create(['estado' => $state, ...($state === 'APROBADA' ? ['inicio' => now()->subHour(), 'fin' => now()] : [])]);
            app(ReleasePolicyManagementService::class)->assign($actor, $r->espacio_id, $policy->id);
            $this->assertSame($policy->id, $r->espacio->fresh()->politica_liberacion_id);
        }
    }

    public function test_deactivation_preserves_history_reactivation_checks_all_assigned_spaces(): void
    {
        $actor = $this->user('Administrador');
        $service = app(ReleasePolicyManagementService::class);
        $r = $this->reservation();
        $p = $r->espacio->politica;
        $e = $this->evaluate($r);
        $service->state($actor, $p->id, false);
        $this->assertFalse($p->fresh()->activa);
        $this->assertSame($p->id, $e->fresh()->politica_id);
        try {
            $service->state($actor, $p->id, true);
            $this->fail('No reactivar durante compromisos.');
        } catch (ValidationException) {
            $this->assertFalse($p->fresh()->activa);
        }
        $this->travelTo($r->fin);
        $service->state($actor, $p->id, true);
        $this->assertTrue($p->fresh()->activa);
        $this->assertSame($e->id, $p->evaluaciones()->sole()->id);
    }

    public function test_inactive_invalid_or_missing_policy_is_not_assignable(): void
    {
        $actor = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        foreach ([PoliticaLiberacion::factory()->create(['activa' => false])->id, PoliticaLiberacion::factory()->create(['requiere_qr' => false])->id, 999999] as $id) {
            try {
                app(ReleasePolicyManagementService::class)->assign($actor, $space->recurso_id, $id);
                $this->fail('Asignación inválida.');
            } catch (ValidationException) {
                $this->assertNull($space->fresh()->politica_liberacion_id);
            }
        }
    }
}
