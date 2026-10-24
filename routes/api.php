<?php

use App\Http\Controllers\IotEventController;
use Illuminate\Support\Facades\Route;

Route::post('/iot/events', IotEventController::class)->name('iot.events.ingest');
