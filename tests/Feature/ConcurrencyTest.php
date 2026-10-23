<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\SesionAcceso;
use App\Models\Usuario;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh --seed --force')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        try {
            $this->artisan('migrate:fresh --seed --force')->assertSuccessful();
        } finally {
            parent::tearDown();
        }
    }

    private function admin(): Usuario
    {
        $user = Usuario::factory()->create();
        (new AsignacionRol)->forceFill(['usuario_id' => $user->id, 'rol_id' => Rol::where('nombre', 'Administrador')->sole()->id, 'asignado_por_id' => $user->id, 'fecha_asignacion' => now()])->save();

        return $user;
    }

    private function race(array $first, array $second): array
    {
        $directory = base_path('.local/testing/concurrency');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $run = bin2hex(random_bytes(8));
        $prefix = $directory.DIRECTORY_SEPARATOR.$run;
        $workers = [];
        try {
            foreach ([$first, $second] as $slot => $input) {
                $worker = new Process([PHP_BINARY, base_path('tests/Support/concurrent-security.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode([...$input, 'run' => $run, 'slot' => $slot], JSON_THROW_ON_ERROR), 20);
                $worker->start();
                $workers[] = $worker;
            }
            $deadline = microtime(true) + 10;
            while (! is_file($prefix.'.0.ready') || ! is_file($prefix.'.1.ready')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Ambos procesos deben alcanzar la barrera');
                }usleep(10000);
            }
            file_put_contents($prefix.'.go', 'go');
            $results = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), 'El proceso concurrente debe finalizar sin error');
                $results[] = trim($worker->getOutput());
            }
            sort($results);

            return $results;
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            } foreach (['.0.ready', '.1.ready', '.go'] as $suffix) {
                if (is_file($prefix.$suffix)) {
                    unlink($prefix.$suffix);
                }
            }
        }
    }

    public function test_two_concurrent_assignments_produce_one_active_pair(): void
    {
        $admin = $this->admin();
        $target = Usuario::factory()->create();
        $role = Rol::where('nombre', 'Docente')->sole();
        $input = ['action' => 'assign', 'actor' => $admin->id, 'target' => $target->id, 'role' => $role->id];
        $this->assertSame(['ok', 'rejected'], $this->race($input, $input));
        $this->assertSame(1, AsignacionRol::where('usuario_id', $target->id)->where('rol_id', $role->id)->activas()->count());
    }

    public function test_two_administrators_cannot_disable_each_other_concurrently(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $this->assertSame(['ok', 'rejected'], $this->race(['action' => 'disable', 'actor' => $first->id, 'target' => $second->id], ['action' => 'disable', 'actor' => $second->id, 'target' => $first->id]));
        $this->assertSame(1, Usuario::where('estado', EstadoUsuario::ACTIVO->value)->count());
    }

    public function test_concurrent_renewal_never_clears_logout_revocation(): void
    {
        $user = $this->admin();
        $id = bin2hex(random_bytes(20));
        (new SesionAcceso)->forceFill(['usuario_id' => $user->id, 'token_hash' => hash('sha256', $id), 'creada_en' => now(), 'expira_en' => now()->addMinutes(config('session.lifetime'))])->save();
        $input = ['actor' => $user->id, 'session' => $id];
        $this->assertSame(['ok', 'ok'], $this->race([...$input, 'action' => 'renew'], [...$input, 'action' => 'revoke']));
        $this->assertNotNull(SesionAcceso::sole()->revocada_en);
        $this->assertDatabaseCount('sesiones_acceso', 1);
    }
}
