<?php

namespace Tests\Feature;

use Tests\QrTestCase;

class ActivityQrVolumeTest extends QrTestCase
{
    public function test_twenty_qr_validations_have_specialized_history_without_new_activity(): void
    {
        $user = $this->user('Docente');
        $reservation = $this->reservation($user);
        $token = $this->token();
        $this->code($token, ['recurso_id' => $reservation->espacio_id]);
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame('VALIDA', $this->qr()->useSpace($user, $reservation, $token)->resultado->value);
        }
        $this->assertDatabaseCount('validaciones_qr', 20);
        $this->assertDatabaseCount('registros_actividad', 0);
    }
}
