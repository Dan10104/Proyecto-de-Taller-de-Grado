<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Services\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request, DashboardService $service)
    {
        return Inertia::render('Dashboard/Index', $service->read($request->user(), $request->validated()))->toResponse($request)->header('Cache-Control', 'private, no-store');
    }
}
