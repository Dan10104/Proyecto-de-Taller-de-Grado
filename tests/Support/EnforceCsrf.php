<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

class EnforceCsrf extends PreventRequestForgery
{
    protected function runningUnitTests()
    {
        return false;
    }
}
