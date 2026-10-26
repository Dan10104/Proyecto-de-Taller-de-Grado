<?php

namespace Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\SecurityTestCase;

class SystemHttpHardeningTest extends SecurityTestCase
{
    public function test_unexpected_error_log_never_records_message_previous_exception_or_request_secrets(): void
    {
        $sink = new TestHandler;
        Log::swap(new \Illuminate\Log\Logger(new Logger('regression', [$sink])));
        $marker = 'SYNTHETIC-PRIVATE-'.bin2hex(random_bytes(12));
        request()->merge(['password' => $marker, 'pregunta' => $marker]);
        app(ExceptionHandler::class)->report(new \RuntimeException($marker, 0, new \RuntimeException($marker)));
        $records = $sink->getRecords();
        $this->assertCount(1, $records);
        $this->assertStringNotContainsString($marker, json_encode($records));
        $this->assertSame(\RuntimeException::class, $records[0]->context['exception_class']);
        $this->assertArrayNotHasKey('exception', $records[0]->context);
    }

    public function test_security_headers_cover_unmatched_routes_health_and_controlled_server_errors(): void
    {
        config(['app.debug' => false]);
        Route::get('/__test_failure', fn () => throw new \RuntimeException('SYNTHETIC-PRIVATE-ERROR'));
        foreach (['/__missing_test' => 404, '/up' => 200, '/__test_failure' => 500] as $url => $status) {
            $response = $this->getJson($url)->assertStatus($status);
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'DENY');
            $response->assertHeader('Referrer-Policy', 'same-origin');
            $response->assertHeader('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'none'");
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $response->assertDontSee('SYNTHETIC-PRIVATE-ERROR', false);
            $response->assertDontSee('SystemHttpHardeningTest', false);
        }
    }

    public function test_production_https_alone_receives_hsts_without_preload_or_subdomain_commitment(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
        $this->app->instance('env', 'production');
        try {
            $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
            $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_cross_origin_iot_and_web_requests_do_not_receive_cors_permission(): void
    {
        foreach (['/login', '/api/iot/events'] as $url) {
            $response = $this->withHeaders(['Origin' => 'https://external.example.test'])->getJson($url);
            $response->assertHeaderMissing('Access-Control-Allow-Origin');
            $response->assertHeaderMissing('Access-Control-Allow-Credentials');
        }
        $this->assertSame([], config('cors.allowed_origins'));
        $this->assertFalse(config('cors.supports_credentials'));
    }

    public function test_production_unexpected_and_explicit_http_500_errors_are_safe_and_spanish(): void
    {
        config(['app.debug' => false]);
        Route::get('/__test_failure', fn () => throw new \RuntimeException('SYNTHETIC-PRIVATE-ERROR'));
        Route::get('/__test_abort', fn () => abort(500, 'SYNTHETIC-PRIVATE-ERROR'));
        foreach (['/__test_failure', '/__test_abort'] as $url) {
            $this->getJson($url)->assertStatus(500)->assertExactJson(['message' => 'El servicio no está disponible temporalmente.']);
            $response = $this->get($url)->assertStatus(500)->assertDontSee('SYNTHETIC-PRIVATE-ERROR', false);
            $response->assertInertia(fn ($page) => $page->component('Error')->where('status', 500));
        }
    }
}
