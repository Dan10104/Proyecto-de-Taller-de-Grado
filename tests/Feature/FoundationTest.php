<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_initial_page_responds_successfully(): void
    {
        $this->get('/')->assertOk()->assertSee('ESPACIO-U');
    }

    public function test_health_endpoint_responds_successfully(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_real_mysql_connection_uses_the_operational_timezone(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $result = DB::selectOne('SELECT 1 AS connected, @@session.time_zone AS timezone');
        $this->assertSame(1, (int) $result->connected);
        $this->assertSame('-04:00', $result->timezone);
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('es', config('app.locale'));
    }
}
