<?php

use App\Exceptions\EquipmentConflictException;
use App\Exceptions\IotException;
use App\Exceptions\ReservationConflictException;
use App\Exceptions\SessionStorageException;
use App\Http\Middleware\EnsureAccessSession;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IotBoundary;
use App\Http\Middleware\PrivateResponse;
use App\Http\Middleware\QrPrivacy;
use App\Services\AccessSessionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(QrPrivacy::class);
        $middleware->prepend(IotBoundary::class);
        $middleware->trimStrings(except: [fn (Request $request) => $request->is('api/iot', 'api/iot/*'), 'identificador']);
        $middleware->convertEmptyStringsToNull(except: [fn (Request $request) => $request->is('api/iot', 'api/iot/*')]);
        $middleware->trimStrings(except: ['qr_token']);
        $middleware->web(prepend: [PrivateResponse::class], append: [HandleInertiaRequests::class]);
        $middleware->alias(['access.session' => EnsureAccessSession::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/inicio');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['qr_token']);
        $exceptions->report(function (Throwable $error) {
            if (request()->is('api/iot', 'api/iot/*', 'iot', 'iot/*')) {
                return false;
            }
        });
        $exceptions->render(function (Throwable $error, Request $request) {
            if ($request->is('api/iot', 'api/iot/*')) {
                return ($error instanceof IotException ? $error : new IotException(503, 'temporarily_unavailable'))->response();
            }
            if ($error instanceof IotException) {
                return $error->response();
            }
        });
        $exceptions->report(function (Throwable $error) {
            if (request()->is('qr', 'qr/*', 'reservas/*/qr', 'prestamos/*/qr/*')) {
                Log::error('Operación QR no disponible.', ['exception_class' => $error::class]);

                return false;
            }
        });
        $exceptions->dontReport([QueryException::class, SessionStorageException::class, ReservationConflictException::class, EquipmentConflictException::class]);
        $exceptions->render(function (EquipmentConflictException $error, Request $request) {
            return $request->expectsJson() ? response()->json(['message' => $error->getMessage()], 409)
                : back()->withErrors(['operacion' => $error->getMessage()]);
        });
        $exceptions->render(function (ReservationConflictException $error, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $error->getMessage()], 409)
                : back()->withErrors(['reserva' => $error->getMessage()])->withInput($request->only(['espacio_id', 'inicio', 'fin', 'proposito', 'motivo_rechazo', 'motivo_cancelacion']));
        });
        $exceptions->render(function (QueryException $error, Request $request) {
            // SQL bindings may contain hashes. Never log the query/exception payload.
            Log::warning('Operación de datos no disponible.', ['sqlstate' => $error->errorInfo[0] ?? 'unknown']);

            return response('El servicio de datos no está disponible temporalmente.', 503);
        });
        $exceptions->render(function (SessionStorageException $error, Request $request) {
            if ($request->hasSession()) {
                try {
                    app(AccessSessionService::class)->revoke($request);
                } catch (Throwable) {
                }
            }

            return response('Servicio de sesión temporalmente no disponible. Intente nuevamente.', 503)
                ->withCookie(Cookie::forget(config('session.cookie'), config('session.path'), config('session.domain')));
        });
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->expectsJson());
        $exceptions->respond(function ($response) {
            if (in_array($response->getStatusCode(), [403, 404, 419, 429], true) && ! request()->expectsJson() && ! request()->is('api/iot', 'api/iot/*')) {
                return Inertia\Inertia::render('Error', ['status' => $response->getStatusCode()])->toResponse(request())->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
