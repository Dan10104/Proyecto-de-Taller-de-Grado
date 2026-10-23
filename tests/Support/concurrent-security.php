<?php

use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AccessSessionService;
use App\Services\AdministrationService;
use App\Support\TestingDatabaseGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Validation\ValidationException;

// Test-only worker: independent process/connection, never a production endpoint.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestingDatabaseGuard::check();
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$barrier = realpath(base_path('.local/testing/concurrency'));
if (! $barrier || ! preg_match('/^[a-f0-9]{16}$/', $input['run']) || ! in_array($input['slot'], [0, 1], true)) {
    exit(2);
}
$prefix = $barrier.DIRECTORY_SEPARATOR.$input['run'];
file_put_contents($prefix.'.'.$input['slot'].'.ready', 'ready');
$deadline = microtime(true) + 10;
while (! is_file($prefix.'.go')) {
    if (microtime(true) > $deadline) {
        exit(3);
    } usleep(10000);
}
try {
    $actor = Usuario::findOrFail($input['actor']);
    $service = app(AdministrationService::class);
    switch ($input['action']) {
        case 'assign': $service->assign($actor, Usuario::findOrFail($input['target']), Rol::findOrFail($input['role']));
            break;
        case 'disable': $service->disableUser($actor, Usuario::findOrFail($input['target']));
            break;
        case 'renew':
        case 'revoke':
            $store = new Store('concurrency', new ArraySessionHandler(config('session.lifetime')), $input['session']);
            $request = Request::create('/inicio');
            $request->setLaravelSession($store);
            $request->setUserResolver(fn () => $actor);
            $sessions = app(AccessSessionService::class);
            if ($input['action'] === 'revoke') {
                $sessions->revoke($request);
            } else {
                $sessions->validateAndRenew($request);
            }
            break;
        default: throw new LogicException('Unknown test action');
    }
    echo 'ok';
} catch (ValidationException|AuthorizationException) {
    echo 'rejected';
} catch (Throwable $error) {
    echo 'unexpected:'.get_class($error);
    exit(1);
}
