<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Foundation', [
    'message' => 'Fundación técnica configurada correctamente',
]))->name('foundation');
