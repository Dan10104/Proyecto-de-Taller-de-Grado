<?php

namespace App\Support;

use App\Services\Notifications\NotificationWindow;

final class DeploymentConfiguration
{
    /** Validate without opening the database or calling optional external services. */
    public function errors(): array
    {
        $errors = [];
        $require = function (bool $valid, string $code) use (&$errors): void {
            if (! $valid) {
                $errors[] = $code;
            }
        };
        $target = config('deployment.target');
        $url = parse_url((string) config('app.url')) ?: [];
        $key = (string) config('app.key');
        $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : false;
        $db = config('database.connections.mysql', []);
        $require(in_array($target, ['cloud-run', 'local-container'], true), 'deployment_target');
        $require(config('app.env') === 'production' && config('app.debug') === false, 'production_mode');
        $require(is_string($decoded) && strlen($decoded) === 32, 'application_key');
        $require(config('app.timezone') === 'America/La_Paz' && ($db['timezone'] ?? null) === '-04:00', 'timezone');
        $require(config('database.default') === 'mysql' && empty($db['url']), 'database_driver');
        $require(! empty($db['username']) && strtolower($db['username']) !== 'root', 'database_identity');
        $require(is_string($db['password'] ?? null) && strlen($db['password']) >= 16, 'database_credential');
        $require(! empty($db['database']) && ! str_contains(strtolower($db['database']), 'testing'), 'database_name');
        foreach (['session.driver', 'session.block_store', 'cache.default', 'queue.default', 'queue.effects_connection', 'iot.rate_store'] as $setting) {
            $require(config($setting) === 'database', str_replace('.', '_', $setting));
        }
        $require(config('session.block') === true && config('session.http_only') === true && config('session.same_site') === 'lax', 'session_protection');
        $require(config('logging.default') === 'stderr' && config('logging.channels.emergency.path') === 'php://stderr', 'logging_destination');
        $require(app(NotificationWindow::class)->start() !== null, 'notification_activation_boundary');
        $require((int) config('queue.connections.database.retry_after') >= 120, 'queue_retry_window');
        if ($target === 'cloud-run') {
            $require(($url['scheme'] ?? '') === 'https' && ! $this->isLocalHost($url['host'] ?? ''), 'https_url');
            $require(config('session.secure') === true, 'secure_cookie');
            $require((bool) preg_match('~^/cloudsql/[a-z0-9-]+:[a-z0-9-]+:[a-z0-9-]+$~D', $db['unix_socket'] ?? ''), 'cloud_sql_socket');
        } elseif ($target === 'local-container') {
            $require(($url['scheme'] ?? '') === 'http' && $this->isLocalHost($url['host'] ?? ''), 'local_loopback_url');
            $require(($db['host'] ?? '') === 'db' && ($db['database'] ?? '') === 'espacio_u_container', 'isolated_local_database');
        }

        return $errors;
    }

    public function isLocalHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.localhost');
    }
}
