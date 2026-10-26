<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest;
use App\Services\Activity\ActivityQueryService;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function index(ActivityRequest $request, ActivityQueryService $queries)
    {
        return Inertia::render('Activity/Index', $queries->listing($request->user(), $request->filters()))->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    public function show(ActivityRequest $request, int $actividad, ActivityQueryService $queries)
    {
        abort_if(count($request->query()) > 0, 422, 'La consulta contiene parámetros no permitidos.');

        return Inertia::render('Activity/Show', ['actividad' => $queries->detail($request->user(), $actividad)])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }
}
