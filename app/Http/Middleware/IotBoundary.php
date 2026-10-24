<?php

namespace App\Http\Middleware;

use App\Exceptions\IotException;
use App\Services\IotRateLimiter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IotBoundary
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('api/iot', 'api/iot/*', 'iot', 'iot/*')) {
            return $next($request);
        }
        config(['app.debug' => false]);
        if ($request->is('api/iot', 'api/iot/*')) {
            try {
                if ($request->getRequestUri() !== '/api/iot/events') {
                    throw new IotException(404, 'not_found');
                }
                if ($request->getRealMethod() !== 'POST') {
                    throw new IotException(405, 'method_not_allowed');
                }
                if (! $request->isSecure() && ! (app()->environment(['local', 'testing']) && in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true))) {
                    throw new IotException(403, 'secure_transport_required');
                }
                app(IotRateLimiter::class)->consume('origin:'.$request->ip(), config('iot.origin_rate_limit_per_minute'));
                $type = $request->headers->all('content-type');
                $encoding = $request->headers->all('content-encoding');
                if (count($type) !== 1 || ! preg_match('/\Aapplication\/json(?:\s*;\s*charset=utf-8)?\z/iD', $type[0]) || (count($encoding) && $encoding !== ['identity'])) {
                    throw new IotException(415, 'unsupported_media_type');
                }
                $max = config('iot.max_body_bytes');
                if ($max < 1 || $max > 1048576) {
                    throw new IotException(503, 'temporarily_unavailable');
                }
                $stream = $request->getContent(true);
                $raw = stream_get_contents($stream, $max + 1);
                if ($raw === false) {
                    throw new IotException(503, 'temporarily_unavailable');
                }
                if (strlen($raw) > $max) {
                    throw new IotException(413, 'payload_too_large');
                }
                $request->attributes->set('iot.raw', $raw);
                $response = $next($request);
            } catch (IotException $error) {
                $response = $error->response();
            } finally {
                $request->attributes->remove('iot.raw');
            }
            Log::info('Recepción IoT.', ['status' => $response->getStatusCode()]);
        } else {
            $response = $next($request);
        }
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
