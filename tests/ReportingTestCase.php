<?php

namespace Tests;

use App\Models\Permiso;
use App\Models\Prestamo;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Reporting\ReportQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

abstract class ReportingTestCase extends SpaceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2090-01-10 08:30:00', 'America/La_Paz'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    protected function reservation(array $data = []): Reserva
    {
        return Reserva::factory()->create($data);
    }

    protected function loanRow(array $data = []): Prestamo
    {
        return Prestamo::factory()->create($data);
    }

    protected function revoke(string $code, string $role = 'Encargado de Laboratorio'): void
    {
        DB::table('roles_permisos')->where('rol_id', Rol::where('nombre', $role)->value('id'))->where('permiso_id', Permiso::where('codigo', $code)->value('id'))->delete();
    }

    protected function report(string $id, array $filters = [], ?Usuario $actor = null): array
    {
        return app(ReportQueryService::class)->preview($actor ?? $this->user('Administrador'), $id, $filters);
    }

    protected function activityFor(Reserva $r, Usuario $actor, array $extra = []): RegistroActividad
    {
        return RegistroActividad::withoutEvents(function () use ($r, $actor, $extra) {
            $a = new RegistroActividad;
            $a->forceFill(['actor_id' => $actor->id, 'usuario_afectado_id' => $r->solicitante_id, 'recurso_id' => $r->espacio_id, 'reserva_id' => $r->id, 'accion' => 'RESERVA_SOLICITADA', 'resultado' => 'EXITO', 'critica' => false, 'fecha_hora' => now(), ...$extra])->save();

            return $a;
        });
    }
}
