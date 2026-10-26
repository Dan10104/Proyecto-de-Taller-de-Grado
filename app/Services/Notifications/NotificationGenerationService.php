<?php

namespace App\Services\Notifications;

use App\Enums\ResultadoRegistroActividad;
use App\Models\RegistroActividad;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationGenerationService
{
    public function __construct(private NotificationRuleRegistry $rules, private NotificationWindow $window, private NotificationContext $context, private NotificationRecipients $recipients) {}

    public function generate(RegistroActividad|int $activity): int
    {
        if (DB::transactionLevel() !== 0) {
            throw new \LogicException('RF21 requiere una transacción nueva sin snapshot previo.');
        }
        if ($this->window->start() === null) {
            return 0; // Disabled until the deployment activation boundary is explicitly configured.
        }
        $id = $activity instanceof RegistroActividad ? (int) $activity->id : $activity;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(fn () => $this->locked($id));
            } catch (QueryException $e) {
                if ($attempt === 3 || ! in_array((int) ($e->errorInfo[1] ?? 0), [1205, 1213], true)) {
                    throw $e;
                }
                usleep(20000 * $attempt);
            }
        }
        throw new \LogicException('Intentos agotados.');
    }

    private function locked(int $id): int
    {
        $activity = RegistroActividad::whereKey($id)->lockForUpdate()->first();
        if (! $activity || $activity->resultado !== ResultadoRegistroActividad::EXITO || ! ($rule = $this->rules->rule($activity->accion))) {
            return 0;
        }
        $time = CarbonImmutable::now('America/La_Paz')->startOfSecond();
        if (! $this->window->includes($activity->fecha_hora, $time)) {
            Log::debug('notifications.outside_window', ['activity_id' => $id]);

            return 0;
        }
        // FIRST consistent read: after waiting for the activity lock, never a range lock.
        $existing = DB::table('notificaciones')->where('registro_actividad_id', $id)->get(['destinatario_id', 'tipo']);
        $context = $this->context->resolve($activity);
        if ($context === null) {
            Log::warning('notifications.invalid_context', ['activity_id' => $id]);

            return 0;
        }
        $recipients = $this->recipients->resolve($activity, $rule['strategy'], $existing->isEmpty());
        $keys = $existing->mapWithKeys(fn ($n) => [$n->destinatario_id.':'.$n->tipo => true]);
        $rows = [];
        foreach ($recipients as $recipient => $direct) {
            if (isset($keys[$recipient.':'.$rule['tipo']->value])) {
                continue;
            }
            $rows[] = $this->rules->template($rule, $context, $direct) + [
                'registro_actividad_id' => $id, 'destinatario_id' => $recipient,
                'tipo' => $rule['tipo']->value, 'creada_en' => $time->toDateTimeString(), 'leida_en' => null,
            ];
        }
        // Chunks limit statement size, not transaction atomicity. A failed chunk rolls back all.
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('notificaciones')->insert($chunk);
        }

        return count($rows);
    }
}
