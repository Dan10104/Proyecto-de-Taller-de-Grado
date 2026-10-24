<?php

namespace App\Exceptions;

class ReleaseDeferredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Evaluación diferida; se reintentará en un próximo barrido.');
    }
}
