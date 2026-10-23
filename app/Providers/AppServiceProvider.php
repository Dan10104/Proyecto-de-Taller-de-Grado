<?php

namespace App\Providers;

use App\Models\Usuario;
use App\Services\AuthorizationService;
use App\Support\CheckedFileSessionHandler;
use App\Support\PermissionCatalog;
use App\Support\TestingDatabaseGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (array_keys(PermissionCatalog::DEFINITIONS) as $code) {
            Gate::define($code, fn (Usuario $user) => app(AuthorizationService::class)->allows($user, $code));
        }
        $this->app->make('session')->extend('file', fn ($app) => new CheckedFileSessionHandler($app['files'], config('session.files'), config('session.lifetime')));
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (in_array($event->command, ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'db:wipe'], true)) {
                $connection = $event->input->hasOption('database') ? $event->input->getOption('database') : null;
                TestingDatabaseGuard::check($connection);
            }
        });
    }
}
