<?php

namespace App\Console\Commands;

use App\Support\DeploymentConfiguration;
use Illuminate\Console\Command;

class CheckDeploymentConfiguration extends Command
{
    protected $signature = 'deployment:check';

    protected $description = 'Validar configuración de infraestructura sin mostrar secretos ni conectar servicios.';

    public function handle(DeploymentConfiguration $configuration): int
    {
        $errors = $configuration->errors();
        if ($errors !== []) {
            $this->error('Deployment configuration rejected: '.implode(', ', $errors));

            return self::FAILURE;
        }
        $this->info('Deployment configuration valid. No credentials displayed.');

        return self::SUCCESS;
    }
}
