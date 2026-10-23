<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response('ESPACIO-U'))->name('foundation');
