<?php

namespace Tests\Feature;

use App\Models\SensorIot;
use Tests\IotTestCase;

class ActivityVolumeTest extends IotTestCase
{
    public function test_twenty_signed_iot_readings_do_not_duplicate_specialized_history_in_activity(): void
    {
        $sensor = SensorIot::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $body = $this->body($sensor, ['occurred_at' => now()->subSeconds(30 - $i)->startOfSecond()->toIso8601String()]);
            $this->signed($sensor, $body)->assertSuccessful();
        }
        $this->assertDatabaseCount('eventos_ocupacion', 20);
        $this->assertDatabaseCount('registros_actividad', 0);
    }
}
