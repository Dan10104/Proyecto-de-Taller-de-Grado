<?php

namespace App\Services\Reporting;

use App\Http\Responses\ReportDownloadResponse;
use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

class ReportGenerationService
{
    public function generate(Usuario $actor, string $id, array $filters, string $format, ?string $preview): ReportDownloadResponse
    {
        $budget = new ReportBudget;
        $queries = app(ReportQueryService::class);
        $prepared = $queries->prepare($actor, $id, $filters, true);
        if ($preview === null || ! hash_equals($queries->fingerprint($prepared), $preview)) {
            throw ValidationException::withMessages(['exportacion' => 'Actualice la vista previa con estos filtros y permisos antes de exportar.']);
        }
        $store = app(ReportTemporaryStore::class);
        $directory = $store->create();
        try {
            $budget->check();
            $dataset = $queries->materialize($prepared, $actor, $directory, $budget);
            $output = app(ReportExportService::class)->render($dataset, $format, $directory, $budget);
            $prepared['access']->revalidate($actor);
            $budget->check($output['file']);
            $response = new ReportDownloadResponse($output['file'], $output['name'], $output['mime'], $store, $directory);
            app(ReportAuditService::class)->record($actor, $dataset->metadata, $format, $dataset->count);

            return $response;
        } catch (\Throwable $e) {
            $store->remove($directory);
            throw $e;
        }
    }
}
