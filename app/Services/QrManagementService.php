<?php

namespace App\Services;

use App\Models\CodigoQr;
use App\Models\Usuario;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QrManagementService
{
    public function __construct(private QrTokenService $tokens, private QrContextService $contexts) {}

    public function issue(Usuario $actor, string $type, int $id, ?string $expiry = null, ?string $sessionHash = null): array
    {
        Validator::make(['vence_en' => $expiry], ['vence_en' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:1000-01-01T00:00|before_or_equal:9999-12-31T23:59'])->validate();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->contexts->transaction($actor, 'qr.gestionar', function ($fresh) use ($type, $id, $expiry) {
                    $target = $this->contexts->target($fresh, $type, $id, true);
                    $previous = CodigoQr::where($target['field'], $id)->orderBy('id')->lockForUpdate()->get();
                    $generated = now()->startOfSecond();
                    $expires = $expiry ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $expiry)->startOfMinute() : null;
                    if ($expires && ! $expires->isAfter($generated)) {
                        throw ValidationException::withMessages(['vence_en' => 'El vencimiento debe ser posterior a la generación.']);
                    }
                    $token = $this->tokens->generate();
                    $hash = $this->tokens->hash($token);
                    foreach ($previous->where('activo', true) as $old) {
                        $old->forceFill(['activo' => false])->save();
                    }
                    $code = new CodigoQr;
                    $code->forceFill([$target['field'] => $id, 'token_hash' => $hash, 'generada_en' => $generated, 'vence_en' => $expires, 'activo' => true])->save();
                    // Render before commit: a renderer failure also restores the previous code.
                    $svg = $this->render($token);
                    unset($token);

                    return ['id' => $code->id, 'svg' => $svg];
                }, $sessionHash);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062 || ! str_contains($e->getMessage(), 'uq_codigo_qr_token_hash')) {
                    throw $e;
                }
            }
        }
        throw ValidationException::withMessages(['qr' => 'No se pudo generar un código nuevo. Inténtelo nuevamente.']);
    }

    public function render(#[\SensitiveParameter] string $token): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle(320, 4), new SvgImageBackEnd)))->writeString($token);
    }

    public function deactivate(Usuario $actor, string $type, int $id, int $codeId, ?string $sessionHash = null): void
    {
        $this->contexts->transaction($actor, 'qr.gestionar', function ($fresh) use ($type, $id, $codeId) {
            $target = $this->contexts->target($fresh, $type, $id, true);
            $code = CodigoQr::where($target['field'], $id)->whereKey($codeId)->lockForUpdate()->firstOrFail();
            $code->forceFill(['activo' => false])->save();
        }, $sessionHash);
    }
}
