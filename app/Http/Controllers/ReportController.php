<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportOptionsRequest;
use App\Http\Requests\ReportRequest;
use App\Services\Reporting\ReportGenerationService;
use App\Services\Reporting\ReportingAccess;
use App\Services\Reporting\ReportQueryService;
use App\Services\Reporting\ReportReferenceService;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportQueryService $queries, ReportRegistry $registry)
    {
        $access = ReportingAccess::for($request->user());
        $data = $request->validated();
        $preview = null;
        if (isset($data['reporte'])) {
            $preview = $queries->preview($request->user(), $data['reporte'], $data['filtros'] ?? [], $data['pagina'] ?? 1, $data['por_pagina'] ?? 25);
            $request->session()->put('reporting.preview', $preview['firma_preview']);
            unset($preview['firma_preview']);
        }

        return Inertia::render('Reports/Index', ['catalogo' => $registry->available($access), 'preview' => $preview, 'limite' => (int) config('reporting.max_rows')])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    public function export(ReportRequest $request, ReportGenerationService $generation)
    {
        $data = $request->validated();
        try {
            return $generation->generate($request->user(), $data['reporte'], $data['filtros'] ?? [], $data['formato'], $request->session()->get('reporting.preview'));
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // The central reporter retains only safe exception metadata, never dataset or filters.
            report($e);
            throw ValidationException::withMessages(['exportacion' => 'No se pudo generar el archivo. Intente nuevamente o reduzca el rango.']);
        }
    }

    public function options(ReportOptionsRequest $request, ReportReferenceService $references, ReportRegistry $registry)
    {
        $data = $request->validated();
        $result = $references->search($registry->get($data['reporte']), $request->user(), $data['campo'], $data['buscar'] ?? '', isset($data['seleccionado']) ? (int) $data['seleccionado'] : null);

        return response()->json(['opciones' => $result])->header('Cache-Control', 'private, no-store');
    }
}
