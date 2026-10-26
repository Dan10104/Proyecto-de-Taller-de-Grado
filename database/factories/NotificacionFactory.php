<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    public function definition(): array
    {
        return ['destinatario_id' => Usuario::factory(), 'registro_actividad_id' => function () {
            $r = Reserva::factory()->create();

            return RegistroActividad::withoutEvents(function () use ($r) {
                $a = new RegistroActividad;
                $a->forceFill(['actor_id' => $r->solicitante_id, 'usuario_afectado_id' => $r->solicitante_id, 'recurso_id' => $r->espacio_id, 'reserva_id' => $r->id, 'accion' => 'RESERVA_SOLICITADA', 'resultado' => 'EXITO', 'critica' => false, 'fecha_hora' => now()])->save();

                return $a->id;
            });
        }, 'titulo' => 'Aviso sintético de prueba', 'mensaje' => 'Solicitud académica sintética registrada.', 'tipo' => 'SOLICITUD', 'creada_en' => now(), 'leida_en' => null];
    }
}
