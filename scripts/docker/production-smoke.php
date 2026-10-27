<?php

use App\Models\Usuario;
use App\Services\AccessSessionService;
use App\Services\Chatbot\ChatbotProvider;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\FakeChatbotProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('deployment.target') !== 'local-container' || config('database.connections.mysql.database') !== 'espacio_u_container') {
    exit(64);
}
try {
    config(['deployment.target' => 'cloud-run', 'app.url' => 'https://app.example.test', 'session.secure' => true]);
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '443'];
    $request = Request::create('http://app.example.test/login', 'GET', server: $server);
    $response = $kernel->handle($request);
    $cookies = $response->headers->getCookies();
    $checks = [
        'login_https_proxy' => $response->getStatusCode() === 200 && $request->isSecure(),
        'url_generation_https' => str_starts_with(url('/dashboard'), 'https://'),
        'secure_cookies' => count($cookies) > 0 && collect($cookies)->every(fn ($cookie) => $cookie->isSecure()),
        'hsts_cloud' => $response->headers->get('Strict-Transport-Security') === 'max-age=31536000',
        'no_mixed_assets' => ! preg_match('/(?:src|href)="http:\/\//', $response->getContent()),
    ];
    $kernel->terminate($request, $response);
    Route::get('/__phase14_controlled_failure', fn () => throw new RuntimeException('SYNTHETIC-PRIVATE-ERROR'));
    $request = Request::create('http://app.example.test/__phase14_controlled_failure', 'GET', server: [...$server, 'HTTP_ACCEPT' => 'application/json']);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $checks['safe_error'] = $response->getStatusCode() === 500 && json_decode($response->getContent(), true) === ['message' => 'El servicio no está disponible temporalmente.'];
    foreach (['Content-Security-Policy', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy'] as $header) {
        $checks['error_'.$header] = $response->headers->has($header);
    }
    // Test-only provider injection; no environment-selectable fake and no HTTP call.
    DB::beginTransaction();
    try {
        config(['chatbot.enabled' => true, 'chatbot.api_key' => 'opaque-local-fixture']);
        $fake = new FakeChatbotProvider;
        app()->instance(ChatbotProvider::class, $fake);
        $actor = Usuario::where('correo', 'docker-admin@espacio-u.test')->firstOrFail();
        $store = new Store('phase14-fake', new ArraySessionHandler(120));
        $store->start();
        $request = Request::create('https://app.example.test/asistente/consultar', 'POST');
        $request->setLaravelSession($store);
        $request->setUserResolver(fn () => $actor);
        app(AccessSessionService::class)->register($request, $actor);
        $result = app(ChatbotService::class)->consult($request, '¿Cómo solicito una reserva?');
        $checks['chatbot_fake_linux'] = $result['status'] === 'ANSWER' && count($fake->calls) === 1;
    } finally {
        DB::rollBack();
    }
    echo json_encode($checks);
    exit(in_array(false, $checks, true) ? 1 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, json_encode(['ok' => false, 'class' => $error::class])."\n");
    exit(1);
}
