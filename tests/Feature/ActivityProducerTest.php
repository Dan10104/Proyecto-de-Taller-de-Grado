<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Models\RegistroActividad;
use App\Services\Activity\ActivityDescription;
use App\Services\Activity\ActivityRecorder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ActivityTestCase;

class ActivityProducerTest extends ActivityTestCase
{
    public static function actions(): array
    {
        return array_combine(array_map(fn ($a) => $a->value, array_slice(ActivityAction::cases(), 18)), array_map(fn ($a) => [$a], array_slice(ActivityAction::cases(), 18)));
    }

    #[DataProvider('actions')]
    public function test_each_new_producer_records_one_safe_success_in_business_transaction(ActivityAction $action): void
    {
        $admin = $this->user('Administrador');
        $operation = $this->operation($action, $admin);
        $operation();
        $row = RegistroActividad::sole();
        $this->assertSame($action->value, $row->accion);
        $this->assertSame('EXITO', $row->resultado->value);
        $this->assertSame($admin->id, $row->actor_id);
        $this->assertFalse($row->critica);
        $this->assertNull($row->proceso_responsable);
        $this->assertLessThanOrEqual(500, mb_strlen($row->descripcion));
        $this->assertSame($row->descripcion, ActivityDescription::safe($action, $row->descripcion));
        foreach (['PRIVADO', $this->password, $admin->correo, 'token_hash', 'AUDIT-DEVICE'] as $private) {
            $this->assertStringNotContainsString($private, $row->descripcion);
        }
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
    }

    #[DataProvider('actions')]
    public function test_mandatory_recording_failure_rolls_back_each_producer_and_records_one_root_error(ActivityAction $action): void
    {
        $admin = $this->user('Administrador');
        $operation = $this->operation($action, $admin);
        $before = $this->businessSnapshot();
        $this->partialMock(ActivityRecorder::class, function ($mock) {
            $mock->shouldReceive('success')->once()->andThrow(new \RuntimeException('PRIVATE-EXCEPTION-FIXTURE'));
        });
        try {
            $operation();
            $this->fail('Debe fallar la operación.');
        } catch (\RuntimeException $e) {
            $this->assertSame('PRIVATE-EXCEPTION-FIXTURE', $e->getMessage());
        }
        $this->assertSame($before, $this->businessSnapshot());
        $row = RegistroActividad::sole();
        $this->assertSame($action->value, $row->accion);
        $this->assertSame('ERROR', $row->resultado->value);
        $this->assertSame('Operación no confirmada; negocio revertido.', $row->descripcion);
        $this->assertFalse($row->critica);
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
        if ($action === ActivityAction::USUARIO_CREADO) {
            $this->assertNull($row->usuario_afectado_id);
        }
        if (in_array($action, [ActivityAction::ESPACIO_CREADO, ActivityAction::EQUIPAMIENTO_CREADO], true)) {
            $this->assertNull($row->recurso_id);
        }
    }

    public function test_catalog_exactly_matches_documented_fifty_six_names(): void
    {
        $source = file_get_contents(base_path('docs/DECISIONS.md'));
        $contract = explode('### AC5.', explode('### AC3.', $source)[1])[0];
        preg_match_all('/^\| ([A-Z_]+) \|/m', $contract, $matches);
        $actual = array_column(ActivityAction::cases(), 'value');
        $this->assertCount(56, $actual);
        $this->assertSame($matches[1], $actual);
        $this->assertCount(56, array_unique($actual));
        $this->assertCount(18, array_filter(ActivityAction::cases(), fn ($a) => $a->legacy()));
        $this->assertCount(4, array_filter(ActivityAction::cases(), fn ($a) => $a->critical()));
        $this->assertCount(9, array_filter(ActivityAction::cases(), fn ($a) => $a->rejectable()));
    }
}
